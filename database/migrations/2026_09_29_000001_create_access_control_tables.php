<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('resource', 100);
            $table->string('action', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['resource', 'action']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('route')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('required_permission', 150)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('employee_id')->constrained('roles')->nullOnDelete();
        });

        Schema::table('user_system_access', function (Blueprint $table) {
            $table->string('role', 100)->nullable()->after('system_id');
        });

        $now = now();
        $superAdminId = DB::table('roles')->insertGetId([
            'name' => 'Superadministrador', 'slug' => 'superadmin',
            'description' => 'Control total del sistema.', 'is_system' => true,
            'active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $auditorId = DB::table('roles')->insertGetId([
            'name' => 'Auditor', 'slug' => 'auditor',
            'description' => 'Consulta de información y bitácoras sin mutaciones.', 'is_system' => true,
            'active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $rhId = DB::table('roles')->insertGetId([
            'name' => 'Administrador RH', 'slug' => 'administrador-rh',
            'description' => 'Gestión de estructura, personal, usuarios e imágenes.', 'is_system' => true,
            'active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $resources = ['administrations', 'organizations', 'departments', 'positions', 'employees', 'users', 'roles', 'permissions', 'menus', 'postal-codes', 'logs', 'sync'];
        $actions = ['view', 'create', 'update', 'delete', 'export'];
        $permissionIds = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $code = "$resource.$action";
                $permissionIds[$code] = DB::table('permissions')->insertGetId([
                    'code' => $code,
                    'resource' => $resource,
                    'action' => $action,
                    'name' => ucfirst($action) . ' ' . $resource,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insert(['role_id' => $superAdminId, 'permission_id' => $permissionId]);
        }
        foreach ($permissionIds as $code => $permissionId) {
            if (str_ends_with($code, '.view') || str_ends_with($code, '.export')) {
                DB::table('permission_role')->insert(['role_id' => $auditorId, 'permission_id' => $permissionId]);
            }
            if (!str_starts_with($code, 'roles.') && !str_starts_with($code, 'permissions.') && !str_starts_with($code, 'menus.')) {
                DB::table('permission_role')->insert(['role_id' => $rhId, 'permission_id' => $permissionId]);
            }
        }

        $menus = [
            ['name' => 'Logs', 'slug' => 'logs', 'route' => '/logs', 'icon' => 'history', 'required_permission' => 'logs.view', 'sort_order' => 10],
            ['name' => 'Catálogos', 'slug' => 'catalogos', 'route' => null, 'icon' => 'library', 'required_permission' => null, 'sort_order' => 20],
            ['name' => 'Administraciones', 'slug' => 'administraciones', 'route' => '/catalogos/administraciones', 'icon' => 'landmark', 'required_permission' => 'administrations.view', 'sort_order' => 21],
            ['name' => 'Organizaciones', 'slug' => 'organizaciones', 'route' => '/catalogos/organizaciones', 'icon' => 'building', 'required_permission' => 'organizations.view', 'sort_order' => 22],
            ['name' => 'Departamentos', 'slug' => 'departamentos', 'route' => '/catalogos/departamentos', 'icon' => 'network', 'required_permission' => 'departments.view', 'sort_order' => 23],
            ['name' => 'Puestos', 'slug' => 'puestos', 'route' => '/catalogos/puestos', 'icon' => 'briefcase', 'required_permission' => 'positions.view', 'sort_order' => 24],
            ['name' => 'Empleados', 'slug' => 'empleados', 'route' => '/catalogos/empleados', 'icon' => 'users', 'required_permission' => 'employees.view', 'sort_order' => 25],
            ['name' => 'Usuarios', 'slug' => 'usuarios', 'route' => '/catalogos/usuarios', 'icon' => 'user-cog', 'required_permission' => 'users.view', 'sort_order' => 26],
            ['name' => 'Roles y permisos', 'slug' => 'roles', 'route' => '/catalogos/roles', 'icon' => 'shield', 'required_permission' => 'roles.view', 'sort_order' => 27],
            ['name' => 'Menús', 'slug' => 'menus', 'route' => '/catalogos/menus', 'icon' => 'menu', 'required_permission' => 'menus.view', 'sort_order' => 28],
            ['name' => 'Códigos postales', 'slug' => 'codigos-postales', 'route' => '/catalogos/codigos-postales', 'icon' => 'map-pin', 'required_permission' => 'postal-codes.view', 'sort_order' => 29],
        ];
        $catalogParentId = null;
        foreach ($menus as $menu) {
            if ($menu['slug'] !== 'catalogos' && $menu['sort_order'] >= 21) {
                $menu['parent_id'] = $catalogParentId;
            }
            $menu['created_at'] = $now;
            $menu['updated_at'] = $now;
            $id = DB::table('menus')->insertGetId($menu);
            if ($menu['slug'] === 'catalogos') $catalogParentId = $id;
        }

        // Los usuarios heredados nunca deben escalar privilegios durante una migración.
        DB::table('users')->whereNull('role_id')->update(['role_id' => $rhId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
        Schema::table('user_system_access', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::dropIfExists('menus');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
