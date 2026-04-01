<?php

namespace App\Http\Controllers;

use App\Models\Administration;

class AdministrationController extends BaseCrudController
{
    protected $modelClass = Administration::class;
    protected $imageDirectory = 'administrations';
    protected $imageFields = ['logo', 'logo_2', 'logo_3'];
    protected $validationRules = [
        'name' => 'required|string|max:255',
        'president_name' => 'required|string|max:255',
        'political_party' => 'nullable|string|max:255',
        'primary_color' => 'nullable|string|max:50',
        'secondary_color' => 'nullable|string|max:50',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after:start_date',
    ];

    public function __construct()
    {
        $this->validationMessages = [
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];

        // Filtro para selectIndex según permisos
        $this->selectIndexQueryCallback = function ($query, Request $request) {
            $auth = Auth::user();
            if ($auth && $auth->role_id > 3) {
                // Ejemplo: si el usuario tiene un departamento asignado, filtrar
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
