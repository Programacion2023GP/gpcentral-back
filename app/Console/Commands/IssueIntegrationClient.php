<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use App\Models\System;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IssueIntegrationClient extends Command
{
    protected $signature = 'integrations:issue-client
        {system : Código del sistema consumidor, por ejemplo gpcenter}
        {--name= : Nombre descriptivo del cliente}
        {--abilities=employees:read : Scopes separados por coma}
        {--expires= : Fecha de expiración YYYY-MM-DD}';

    protected $description = 'Emite o rota una credencial máquina-a-máquina para un sistema externo';

    public function handle(): int
    {
        $systemCode = Str::lower(trim((string) $this->argument('system')));
        if (!preg_match('/^[a-z0-9-]{2,50}$/', $systemCode)) {
            $this->error('El código del sistema sólo puede contener minúsculas, números y guiones.');
            return self::FAILURE;
        }

        $system = System::firstOrCreate(
            ['code' => $systemCode],
            [
                'name' => $this->option('name') ?: Str::headline($systemCode),
                'is_active' => true,
                'active' => true,
            ]
        );

        if (!$system->active || !$system->is_active) {
            $this->error('El sistema existe pero está inactivo. Reactívalo antes de emitir credenciales.');
            return self::FAILURE;
        }

        $abilities = collect(explode(',', (string) $this->option('abilities')))
            ->map(fn ($ability) => trim($ability))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($abilities === []) {
            $this->error('Debe indicar al menos un scope.');
            return self::FAILURE;
        }

        $secret = Str::random(64);
        $clientId = $systemCode;

        ApiClient::updateOrCreate(
            ['client_id' => $clientId],
            [
                'system_id' => $system->id,
                'name' => $this->option('name') ?: $system->name,
                'secret_hash' => Hash::make($secret),
                'abilities' => $abilities,
                'expires_at' => $this->option('expires'),
                'revoked_at' => null,
                'active' => true,
            ]
        );

        $this->warn('Guarda el secreto ahora: no puede recuperarse después.');
        $this->line('X-Client-Id: '.$clientId);
        $this->line('X-API-Key: '.$secret);
        $this->line('Scopes: '.implode(', ', $abilities));

        return self::SUCCESS;
    }
}
