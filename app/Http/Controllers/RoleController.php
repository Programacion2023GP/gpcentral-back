<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends BaseCrudController
{
    protected $modelClass = Role::class;
    protected $selectLabel = 'name';

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        return ObjResponse::success(Role::with('permissions')->orderBy('name')->get());
    }

    public function createOrUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => ['required', 'string', 'max:100', Rule::unique('roles')->ignore($request->id)],
            'description' => 'nullable|string|max:1000',
            'active' => 'boolean',
            'permission_ids' => 'array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        return DB::transaction(function () use ($request) {
            $role = $request->id ? Role::findOrFail($request->id) : new Role();
            if ($role->is_system && $role->slug === 'superadmin' && $request->has('active') && !$request->boolean('active')) {
                return ObjResponse::error('El rol superAdmin no puede desactivarse', 422);
            }
            $role->fill($request->only(['name', 'slug', 'description', 'active']))->save();
            if ($role->slug !== 'superadmin') $role->permissions()->sync($request->input('permission_ids', []));
            return ObjResponse::success($role->load('permissions'), $request->id ? 'Rol actualizado' : 'Rol creado');
        });
    }

    public function permissionMatrix()
    {
        return ObjResponse::success(Permission::orderBy('resource')->orderBy('action')->get()->groupBy('resource'));
    }
}
