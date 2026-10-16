<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $roleId = DB::table('roles')->where('slug', 'superadmin')->value('id');
        if (!$roleId) {
            throw new RuntimeException('No existe el rol superadmin. Ejecuta primero las migraciones.');
        }

        $username = env('SUPERADMIN_USERNAME', 'sa');
        $email = env('SUPERADMIN_EMAIL', 'sa@gomezpalacio.gob.mx');
        $password = env('SUPERADMIN_PASSWORD', 'desarrollo') ?? 'desarrollo';
        if (!$password || strlen($password) < 12) {
            throw new RuntimeException('SUPERADMIN_PASSWORD debe tener al menos 12 caracteres.');
        }

        DB::table('users')->updateOrInsert(
            ['username' => $username],
            [
                'employee_id' => null,
                'role_id' => $roleId,
                'email' => $email,
                'password' => Hash::make($password),
                'active' => true,
                'deleted_at' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}