<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\System;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrationAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_versioned_employee_lookup_requires_integration_credentials(): void
    {
        $this->getJson('/api/v1/employees/by-code/612024')
            ->assertUnauthorized()
            ->assertJsonPath('status', false);
    }

    public function test_legacy_employee_lookup_requires_integration_credentials(): void
    {
        $this->getJson('/api/employees/getBy/employee_code/612024')
            ->assertUnauthorized()
            ->assertJsonPath('status', false);
    }

    public function test_arbitrary_legacy_lookup_fields_are_not_routable(): void
    {
        $this->getJson('/api/employees/getBy/email/example@gomezpalacio.gob.mx')
            ->assertNotFound();
    }

    public function test_authorized_client_can_read_employee_by_code(): void
    {
        $secret = Str::random(64);
        $suffix = Str::lower(Str::random(10));
        $system = System::create([
            'code' => 'test-'.$suffix,
            'name' => 'Test integration',
            'is_active' => true,
            'active' => true,
        ]);
        ApiClient::create([
            'system_id' => $system->id,
            'name' => 'Test client',
            'client_id' => 'client-'.$suffix,
            'secret_hash' => Hash::make($secret),
            'abilities' => ['employees:read'],
            'active' => true,
        ]);
        $employee = Employee::create([
            'employee_code' => 'T'.$suffix,
            'hire_date' => now()->toDateString(),
            'employment_type' => 'confianza',
            'active' => true,
        ]);
        EmployeeDetail::create([
            'employee_id' => $employee->id,
            'name' => 'Persona',
            'plast_name' => 'Prueba',
            'start_date' => now()->toDateString(),
            'active' => true,
        ]);

        $this->withHeaders([
            'X-Client-Id' => 'client-'.$suffix,
            'X-API-Key' => $secret,
        ])->getJson('/api/v1/employees/by-code/T'.$suffix)
            ->assertOk()
            ->assertJsonPath('result.employee_code', 'T'.$suffix)
            ->assertJsonPath('result.full_name', 'Persona Prueba ');
    }
}
