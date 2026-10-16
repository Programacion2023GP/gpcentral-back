<?php

namespace App\Console\Commands;

use App\Services\EmployeeSyncService;
use Illuminate\Console\Command;

class PreviewEmployeeSync extends Command
{
    protected $signature = 'employees:sync-preview {file? : Ruta absoluta al CSV} {--type=confianza} {--source=nomina}';
    protected $description = 'Genera una conciliación de nómina sin aplicar cambios automáticamente';

    public function handle(EmployeeSyncService $service): int
    {
        $file = $this->argument('file') ?: config('services.employee_sync.file');
        if (!$file || !is_file($file)) {
            $this->error('No se encontró el CSV. Define EMPLOYEE_SYNC_FILE o pasa la ruta como argumento.');
            return self::FAILURE;
        }
        $type = (string) $this->option('type');
        if (!in_array($type, ['confianza', 'sindicalizado'], true)) {
            $this->error('El tipo debe ser confianza o sindicalizado.');
            return self::INVALID;
        }
        $run = $service->preview($file, $type, (string) $this->option('source'), null, 'scheduled');
        $this->info("Conciliación {$run->id} creada. Requiere revisión y aplicación desde GP Central.");
        $this->table(['Resultado', 'Registros'], collect($run->summary)->map(fn ($count, $kind) => [$kind, $count])->values());
        return self::SUCCESS;
    }
}
