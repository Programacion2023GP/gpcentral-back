<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\VW_User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DepartmentController extends BaseCrudController
{
    protected $modelClass = Department::class;
    protected $imageDirectory = 'departments';
    protected $imageFields = ['seal_image'];
    protected $defaultOrderBy = ['id' => 'desc'];
    protected $useAuthFilter = true;
    protected $selectLabel = ['CONCAT(COALESCE(letters, "")," - ",department)'];

    public function __construct()
    {
        $this->validationRules = [
            'letters'   => 'string|max:255',
            'department' => 'string|max:255',
        ];

        $this->selectIndexQueryCallback = function ($query, Request $request) {
            $auth = Auth::user();
            if ($auth && $auth->role_id > 3) {
                $userEmployee = VW_User::where('id', $auth->id)->first();
                if ($userEmployee && !Str::contains($userEmployee->more_permissions ?? '', ['Ver Todas las Situaciones', 'todas'])) {
                    $query->where('id', $userEmployee->department_id);
                }
            }
        };
    }

    /**
     * Sobrescribir createOrUpdate para manejar versionado.
     */
    public function createOrUpdate(Request $request, $id = null)
    {
        try {
            // Validar
            $validator = $this->validateRequest($request, $id);
            if ($validator->fails()) {
                return ObjResponse::validationError($validator->errors()->toArray());
            }

            if ($id) {
                // Es actualización: cerrar versión actual y crear nueva
                $current = Department::where('id', $id)->whereNull('end_date')->first();
                if (!$current) {
                    return ObjResponse::notFound('Versión actual no encontrada');
                }
                $uuid = $current->uuid;
                $effectiveDate = $request->get('start_date', now()->toDateString());

                // Cerrar versión actual
                $current->end_date = $effectiveDate;
                $current->active = false;
                $current->save();

                // Crear nueva versión
                $data = $request->except($this->imageFields);
                $data['uuid'] = $uuid;
                $data['start_date'] = $effectiveDate;
                $data['end_date'] = null;
                $data['active'] = true;
                $new = Department::create($data);
            } else {
                // Creación nueva
                $data = $request->except($this->imageFields);
                $data['uuid'] = (string) Str::uuid();
                $data['start_date'] = $request->get('start_date', now()->toDateString());
                $data['active'] = true;
                $new = Department::create($data);
            }

            // Procesar imágenes (se asocian al nuevo registro)
            foreach ($this->imageFields as $field) {
                $this->ImageUp(
                    $request,
                    $field,
                    $this->imageDirectory,
                    $new->id,
                    strtoupper($field),
                    is_null($id),
                    "noImage.png",
                    $new
                );
            }

            $message = $id ? 'Departamento actualizado' : 'Departamento creado';
            return ObjResponse::success($new, $message);
        } catch (\Exception $ex) {
            Log::error("DepartmentController ~ createOrUpdate: " . $ex->getMessage());
            return ObjResponse::serverError('Error al guardar', $ex);
        }
    }

    /**
     * Mostrar versión activa en una fecha.
     */
    public function show($id, Request $request)
    {
        try {
            $date = $request->get('date', now()->toDateString());
            $department = Department::where('id', $id)
                ->where('start_date', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')->orWhere('end_date', '>', $date);
                })
                ->first();

            if (!$department) {
                return ObjResponse::notFound('Departamento no encontrado para la fecha especificada');
            }

            return ObjResponse::success($department);
        } catch (\Exception $ex) {
            Log::error("DepartmentController ~ show: " . $ex->getMessage());
            return ObjResponse::serverError('Error al obtener departamento', $ex);
        }
    }
}



/*
namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $departments = Department::with('organization')
            ->activeAt($date)
            ->paginate();

        return DepartmentResource::collection($departments);
    }

    public function show($uuid, Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $department = Department::where('uuid', $uuid)
            ->activeAt($date)
            ->firstOrFail();

        return new DepartmentResource($department);
    }

    public function store(StoreDepartmentRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['uuid'] = (string) Str::uuid();
            $data['start_date'] = $request->get('start_date', now()->toDateString());
            $data['active'] = true;
            $department = Department::create($data);
            return new DepartmentResource($department);
        });
    }

    public function update(StoreDepartmentRequest $request, $uuid)
    {
        $date = $request->get('effective_date', now()->toDateString());

        DB::transaction(function () use ($request, $uuid, $date) {
            // Cerrar versión actual
            $current = Department::where('uuid', $uuid)->whereNull('end_date')->first();
            if ($current) {
                $current->end_date = $date;
                $current->active = false;
                $current->save();
            }

            // Crear nueva versión
            $data = $request->validated();
            $data['uuid'] = $uuid;
            $data['start_date'] = $date;
            $data['active'] = true;
            $new = Department::create($data);

            return new DepartmentResource($new);
        });
    }

    public function destroy($uuid)
    {
        // Soft delete: marcar todas las versiones con deleted_at y cerrar la actual
        DB::transaction(function () use ($uuid) {
            $current = Department::where('uuid', $uuid)->whereNull('end_date')->first();
            if ($current) {
                $current->end_date = now()->toDateString();
                $current->active = false;
                $current->save();
            }
            Department::where('uuid', $uuid)->delete(); // soft delete todas las versiones
        });

        return response()->noContent();
    }
}