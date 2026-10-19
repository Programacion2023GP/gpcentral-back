<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ObjResponse;
use App\Models\VW_Employee;
use Illuminate\Http\JsonResponse;

class EmployeeLookupController extends Controller
{
    public function showByCode(string $employeeCode): JsonResponse
    {
        if (!preg_match('/^[A-Za-z0-9._-]{1,50}$/', $employeeCode)) {
            return ObjResponse::validationError([
                'employee_code' => ['El número de empleado no tiene un formato válido.'],
            ]);
        }

        $employee = Employee::where('employee_code', $employeeCode)
            ->where('active', true)
            ->first();

        if (!$employee) {
            return ObjResponse::notFound('Empleado activo no encontrado');
        }

        $record = VW_Employee::where('employee_id', $employee->id)->first();

        if (!$record) {
            return ObjResponse::notFound('Expediente vigente no encontrado');
        }

        return ObjResponse::success([
            'employee_id' => $record->employee_id,
            'employee_code' => $record->employee_code,
            'hire_date' => optional($employee->hire_date)->toDateString(),
            'employment_type' => $employee->employment_type,
            'full_name' => $record->full_name,
            'name' => $record->name,
            'plast_name' => $record->plast_name,
            'mlast_name' => $record->mlast_name,
            'rfc' => $record->rfc,
            'curp' => $record->curp,
            'gender' => $record->gender,
            'cellphone' => $record->cellphone,
            'avatar' => $record->avatar,
            'signature_image' => $record->signature_image,
            'position_uuid' => $record->position_uuid,
            'position_name' => $record->position_name,
            'department_uuid' => $record->department_uuid,
            'department_name' => $record->department_name,
            'department_logo' => $record->department_logo,
            'department_seal_image' => $record->seal_image,
            'organization_id' => $record->organization_id,
            'organization_name' => $record->organization_name,
            'administration_id' => $record->administration_id,
            'administration_name' => $record->administration_name,
            'administration_logo' => $record->administration_logo,
        ], 'Empleado encontrado');
    }
}
