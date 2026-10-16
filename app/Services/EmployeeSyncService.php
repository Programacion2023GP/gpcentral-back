<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeDetail;
use App\Models\Position;
use App\Models\SyncChange;
use App\Models\SyncRun;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeSyncService
{
    public function preview(UploadedFile|string $file, string $employmentType, string $source, ?int $userId, string $trigger = 'manual'): SyncRun
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $hash = hash_file('sha256', $path);
        $run = SyncRun::create([
            'requested_by' => $userId,
            'source' => $source,
            'trigger' => $trigger,
            'status' => 'preview',
            'source_hash' => $hash,
            'started_at' => now(),
        ]);

        $rows = $this->readCsv($path);
        $seen = [];
        $summary = array_fill_keys(['create', 'update', 'rehire', 'terminate', 'unchanged', 'conflict', 'invalid'], 0);

        foreach ($rows as $row) {
            $incoming = $this->normalize($row, $employmentType, $source);
            $code = $incoming['employee_code'] ?? '';
            if (!$code || isset($seen[$code])) {
                $type = isset($seen[$code]) ? 'conflict' : 'invalid';
                $this->record($run, null, $code ?: '(sin número)', $type, $incoming, null, ['employee_code' => ['reason' => $type]]);
                $summary[$type]++;
                continue;
            }
            $seen[$code] = true;
            $employee = Employee::with(['currentDetail', 'currentAssignment'])->where('employee_code', $code)->first();
            if (!$employee) {
                $type = 'create';
                $differences = collect($incoming)->map(fn($value) => ['from' => null, 'to' => $value])->all();
            } else {
                $current = $this->snapshot($employee);
                $differences = $this->differences($current, $incoming);
                $type = !$employee->active ? 'rehire' : (empty($differences) ? 'unchanged' : 'update');
            }
            $this->record($run, $employee, $code, $type, $incoming, $employee ? $this->snapshot($employee) : null, $differences);
            $summary[$type]++;
        }

        Employee::where('external_source', $source)->where('active', true)
            ->whereNotIn('employee_code', array_keys($seen))->each(function (Employee $employee) use ($run, &$summary) {
                $this->record($run, $employee, $employee->employee_code, 'terminate', [], $this->snapshot($employee), ['active' => ['from' => true, 'to' => false]]);
                $summary['terminate']++;
            });

        $run->update(['summary' => $summary]);
        return $run->load('changes');
    }

    public function apply(SyncRun $run, array $selectedIds = []): SyncRun
    {
        $run->update(['status' => 'running']);
        $query = $run->changes()->where('selected', true)->whereNull('applied_at')->whereNotIn('change_type', ['unchanged', 'conflict', 'invalid']);
        if ($selectedIds) $query->whereIn('id', $selectedIds);

        DB::transaction(function () use ($query) {
            foreach ($query->lockForUpdate()->get() as $change) {
                $change->change_type === 'terminate' ? $this->terminate($change) : $this->upsert($change);
                $change->update(['applied_at' => now()]);
            }
        });
        $run->update(['status' => 'completed', 'completed_at' => now()]);
        return $run->fresh('changes');
    }

    private function upsert(SyncChange $change): void
    {
        $data = $change->incoming_data;
        $employee = Employee::firstOrNew(['employee_code' => $change->employee_code]);
        $employee->fill(Arr::only($data, ['hire_date', 'employment_type', 'external_source', 'external_id']));
        $employee->active = true;
        $employee->termination_date = null;
        $employee->save();

        $personal = Arr::only($data, ['name', 'plast_name', 'mlast_name', 'rfc', 'curp', 'gender', 'cellphone']);
        $currentPersonal = $employee->currentDetail;
        if (!$currentPersonal || $this->differences($currentPersonal->only(array_keys($personal)), $personal)) {
            $currentPersonal?->update(['end_date' => $data['effective_date'], 'active' => false]);
            EmployeeDetail::create($personal + ['employee_id' => $employee->id, 'start_date' => $data['effective_date'], 'active' => true]);
        }

        $department = Department::current()->whereRaw('UPPER(name) = ?', [Str::upper($data['department_name'])])->first();
        $position = Position::current()->whereRaw('UPPER(name) = ?', [Str::upper($data['position_name'])])->first();
        if ($department && $position) {
            $assignment = $employee->currentAssignment;
            if (!$assignment || $assignment->department_uuid !== $department->uuid || $assignment->position_uuid !== $position->uuid) {
                $assignment?->update(['end_date' => $data['effective_date'], 'active' => false]);
                EmployeeAssignment::create([
                    'employee_id' => $employee->id,
                    'department_uuid' => $department->uuid,
                    'position_uuid' => $position->uuid,
                    'start_date' => $data['effective_date'],
                    'active' => true,
                ]);
            }
        }
    }

    private function terminate(SyncChange $change): void
    {
        $employee = Employee::find($change->employee_id);
        if (!$employee) return;
        $date = now()->toDateString();
        $employee->update(['active' => false, 'termination_date' => $date]);
        $employee->currentDetail?->update(['end_date' => $date, 'active' => false]);
        $employee->currentAssignment?->update(['end_date' => $date, 'active' => false]);
        if ($employee->user) {
            $employee->user->update(['active' => false]);
            $employee->user->tokens()->delete();
            $employee->user->systemAccesses()->update(['end_date' => $date, 'active' => false]);
        }
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = array_map('trim', fgetcsv($handle) ?: []);
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) === count($header)) $rows[] = array_combine($header, $values);
        }
        fclose($handle);
        return $rows;
    }

    private function normalize(array $row, string $type, string $source): array
    {
        $get = fn(array $keys) => trim((string) collect($keys)->map(fn($key) => $row[$key] ?? null)->first(fn($value) => $value !== null));
        $hireRaw = $get(['Fecha Alta', 'hire_date', 'fecha_alta']);
        try { $hireDate = Carbon::parse($hireRaw)->toDateString(); } catch (\Throwable) { $hireDate = null; }
        return [
            'employee_code' => $get(['Numero_Nomina', 'employee_code', 'numero_nomina']),
            'hire_date' => $hireDate,
            'employment_type' => $type,
            'external_source' => $source,
            'external_id' => $get(['external_id', 'Numero_Nomina', 'employee_code']) ?: null,
            'name' => $get(['Nombre', 'name']),
            'plast_name' => $get(['A. Paterno', 'plast_name']),
            'mlast_name' => $get(['A. Materno', 'mlast_name']) ?: null,
            'rfc' => Str::upper($get(['RFC', 'rfc'])) ?: null,
            'curp' => Str::upper($get(['CURP', 'curp'])) ?: null,
            'gender' => Str::upper($get(['Sexo', 'gender'])) ?: null,
            'cellphone' => $get(['Telefono', 'cellphone']) ?: null,
            'department_name' => $get(['Departamento', 'department']),
            'position_name' => $get(['Puesto', 'position']),
            'effective_date' => $hireDate ?: now()->toDateString(),
        ];
    }

    private function snapshot(Employee $employee): array
    {
        return array_merge($employee->only(['employee_code', 'hire_date', 'employment_type', 'external_source', 'external_id']),
            $employee->currentDetail?->only(['name', 'plast_name', 'mlast_name', 'rfc', 'curp', 'gender', 'cellphone']) ?? [],
            ['department_name' => $employee->currentAssignment?->department?->name, 'position_name' => $employee->currentAssignment?->position?->name]);
    }

    private function differences(array $current, array $incoming): array
    {
        $ignore = ['effective_date'];
        $diff = [];
        foreach ($incoming as $field => $value) {
            if (in_array($field, $ignore, true)) continue;
            $from = $current[$field] ?? null;
            $from = $from instanceof \DateTimeInterface ? $from->format('Y-m-d') : $from;
            if ((string) ($from ?? '') !== (string) ($value ?? '')) $diff[$field] = ['from' => $from, 'to' => $value];
        }
        return $diff;
    }

    private function record(SyncRun $run, ?Employee $employee, string $code, string $type, ?array $incoming, ?array $current, array $differences): void
    {
        SyncChange::create([
            'sync_run_id' => $run->id, 'employee_id' => $employee?->id, 'employee_code' => $code,
            'change_type' => $type, 'incoming_data' => $incoming, 'current_data' => $current,
            'differences' => $differences, 'selected' => !in_array($type, ['unchanged', 'conflict', 'invalid'], true),
        ]);
    }
}
