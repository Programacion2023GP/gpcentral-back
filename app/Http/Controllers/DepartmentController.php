<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ObjResponse;
use App\Models\VW_Department;
use App\Models\VW_User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DepartmentController extends BaseCrudController
{
    protected $modelClass = Department::class;
    protected $modelClassView = VW_Department::class;
    protected $imageDirectory = 'departments';
    protected $imageFields = ['seal_image'];
    protected $defaultOrderBy = ['id' => 'desc'];
    protected $useAuthFilter = true;
    protected $selectLabel = 'CONCAT(COALESCE(code, "")," - ",name)';

    public function __construct()
    {
        $this->validationRules = [
            'code'   => 'string|max:255',
            'name' => 'string|max:255',
        ];

        // $this->selectIndexQueryCallback = function ($query, Request $request) {
        //     $auth = Auth::user();
        //     if ($auth && $auth->role_id > 3) {
        //         $userEmployee = VW_User::where('id', $auth->id)->first();
        //         if ($userEmployee && !Str::contains($userEmployee->more_permissions ?? '', ['Ver Todas las Situaciones', 'todas'])) {
        //             $query->where('id', $userEmployee->department_id);
        //         }
        //     }
        // };
    }

    /**
     * Sobrescribir createOrUpdate para manejar versionado.
     */
    public function createOrUpdate(Request $request)
    {
        try {
            $id = $request->id ?? null;
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
    public function show($id, Request $request,  $internal = false)
    {
        Log::info("Porque entre aqui al show?");
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

    public function directors($uuid): JsonResponse
    {
        try {
            $department = Department::where('uuid', $uuid)->whereNull('end_date')->firstOrFail();
            $history = $department->directors;

            $data = $history->map(function ($assignment) {
                $detail = $assignment->employee->currentDetail;
                return [
                    'id' => $assignment->id,
                    'employee_id' => $assignment->employee_id,
                    'employee_name' => $detail
                        ? trim("{$detail->name} {$detail->plast_name} {$detail->mlast_name}")
                        : null,
                    'position_uuid' => $assignment->position_uuid,
                    'position_name' => $assignment->position->name ?? null,
                    'start_date' => $assignment->start_date,
                    'end_date' => $assignment->end_date,
                ];
            });

            return ObjResponse::success($data, 'Historial de directores obtenido');
        } catch (\Exception $ex) {
            Log::error("DepartmentController ~ directors: " . $ex->getMessage());
            return ObjResponse::serverError('Error al obtener historial de directores', $ex);
        }
    }
}