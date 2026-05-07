<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\EmployeeAssignment;
use App\Models\ObjResponse;
use App\Models\User;
use App\Models\VW_Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployeeController extends BaseCrudController
{
    protected $modelClass = Employee::class;
    protected $imageDirectory = 'employees';
    protected $imageFields = ['avatar', 'signature_image']; // Ajusta según tus campos
    protected $validationRules = [
        'employee_code' => 'required|string|max:50|unique:employees,employee_code',
        'hire_date' => 'required|date',
    ];
    protected $selectLabel = ['CONCAT(employee_code, " - ", name, " ", plast_name)']; // Si usas vista

    public function __construct()
    {
        $this->indexQueryCallback = function ($query, Request $request) {
            $query->with(['currentDetail', 'currentAssignment.position.department.organization']);
        };
    }

    /**
     * Mostrar lista de usuarios.
     *
     * 
     */
    public function index(Response $response)
    {
        ObjResponse::default();
        try {
            $roleAuth = Auth::user()->role_id;
            // $list = VW_User::where("role_id", ">=", $roleAuth)
            // ->orderBy('id', 'desc');
            $list = VW_Employee::orderBy('id', 'desc');
            if ($roleAuth > 1) $list = $list->where("active", true);
            $list = $list->get();

            return ObjResponse::success($list)->getData();
            // $response->data["message"] = 'Peticion satisfactoria | Lista de usuarios.';
            // $response->data["result"] = $list;

            // Http::get(route('api.notifications'));
        } catch (\Exception $ex) {
            $msg = "UserController ~ index ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            return ObjResponse::error($msg);
        }
        // return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear empleado con sus detalles y asignación inicial.
     */
    public function createOrUpdate(Request $request, $id = null)
    {
        try {
            // Validar datos básicos de employee
            $validator = $this->validateRequest($request, $id);
            if ($validator->fails()) {
                return ObjResponse::validationError($validator->errors()->toArray());
            }

            DB::beginTransaction();

            if ($id) {
                $employee = Employee::find($id);
                if (!$employee) {
                    return ObjResponse::notFound('Empleado no encontrado');
                }
                $employee->update($request->only(['employee_code', 'hire_date']));
            } else {
                $employee = Employee::create($request->only(['employee_code', 'hire_date']));
            }

            // Manejar detalles del empleado (versionado)
            $this->saveEmployeeDetail($employee, $request, $id ? false : true);

            // Manejar asignación a puesto (versionado)
            if ($request->has('position_uuid')) {
                $this->saveEmployeeAssignment($employee, $request, $id ? false : true);
            }

            // Procesar imágenes
            foreach ($this->imageFields as $field) {
                $this->ImageUp(
                    $request,
                    $field,
                    $this->imageDirectory,
                    $employee->id,
                    strtoupper($field),
                    is_null($id),
                    "noImage.png",
                    $employee
                );
            }

            DB::commit();

            $message = $id ? 'Empleado actualizado' : 'Empleado creado';
            return ObjResponse::success($employee->load('currentDetail', 'currentAssignment'), $message);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error("EmployeeController ~ createOrUpdate: " . $ex->getMessage());
            return ObjResponse::serverError('Error al guardar empleado', $ex);
        }
    }

    /**
     * Guardar/actualizar detalle del empleado (versión).
     */
    private function saveEmployeeDetail($employee, Request $request, $isNew)
    {
        $detailData = $request->only([
            'name',
            'plast_name',
            'mlast_name',
            'rfc',
            'curp',
            'sex',
            'phone',
            'avatar',
            'signature_image'
        ]);

        // Si es actualización y existen datos, cerrar versión actual
        if (!$isNew && $employee->currentDetail) {
            $employee->currentDetail->update([
                'end_date' => now()->toDateString(),
                'active' => false
            ]);
        }

        // Crear nuevo detalle
        $detailData['employee_id'] = $employee->id;
        $detailData['start_date'] = $request->get('detail_start_date', now()->toDateString());
        $detailData['end_date'] = null;
        $detailData['active'] = true;
        EmployeeDetail::create($detailData);
    }

    /**
     * Guardar/actualizar asignación a puesto.
     */
    private function saveEmployeeAssignment($employee, Request $request, $isNew)
    {
        if (!$isNew && $employee->currentAssignment) {
            $employee->currentAssignment->update([
                'end_date' => now()->toDateString(),
                'active' => false
            ]);
        }

        EmployeeAssignment::create([
            'employee_id' => $employee->id,
            'position_uuid' => $request->position_uuid,
            'start_date' => $request->get('assignment_start_date', now()->toDateString()),
            'end_date' => null,
            'active' => true
        ]);
    }

    /**
     * Mostrar snapshot del empleado en una fecha.
     */
    public function snapshot($id, Request $request)
    {
        try {
            $date = $request->get('date', now()->toDateString());

            $employee = Employee::with([
                'details' => function ($q) use ($date) {
                    $q->where('start_date', '<=', $date)
                        ->where(function ($q) use ($date) {
                            $q->whereNull('end_date')->orWhere('end_date', '>', $date);
                        });
                },
                'assignments' => function ($q) use ($date) {
                    $q->where('start_date', '<=', $date)
                        ->where(function ($q) use ($date) {
                            $q->whereNull('end_date')->orWhere('end_date', '>', $date);
                        })->with(['position' => function ($q) use ($date) {
                            $q->where('start_date', '<=', $date)
                                ->where(function ($q) use ($date) {
                                    $q->whereNull('end_date')->orWhere('end_date', '>', $date);
                                })->with(['department' => function ($q) use ($date) {
                                    $q->where('start_date', '<=', $date)
                                        ->where(function ($q) use ($date) {
                                            $q->whereNull('end_date')->orWhere('end_date', '>', $date);
                                        });
                                }]);
                        }]);
                },
                'user'
            ])->find($id);

            if (!$employee) {
                return ObjResponse::notFound('Empleado no encontrado');
            }

            // Estructurar respuesta
            $detail = $employee->details->first();
            $assignment = $employee->assignments->first();
            $position = $assignment?->position;
            $department = $position?->department;

            $data = [
                'employee' => [
                    'id' => $employee->id,
                    'code' => $employee->employee_code,
                    'hire_date' => $employee->hire_date,
                ],
                'personal' => $detail ? [
                    'name' => $detail->name,
                    'plast_name' => $detail->plast_name,
                    'mlast_name' => $detail->mlast_name,
                    'rfc' => $detail->rfc,
                    'curp' => $detail->curp,
                    'sex' => $detail->sex,
                    'phone' => $detail->phone,
                    'avatar' => $detail->avatar,
                    'signature' => $detail->signature_image,
                ] : null,
                'position' => $position ? [
                    'uuid' => $position->uuid,
                    'name' => $position->name,
                    'department' => $department ? [
                        'uuid' => $department->uuid,
                        'name' => $department->name,
                        'organization' => $department->organization->name ?? null,
                    ] : null,
                ] : null,
                'user' => $employee->user ? [
                    'username' => $employee->user->username,
                    'email' => $employee->user->email,
                    'role' => $employee->user->role->name ?? null,
                ] : null,
            ];

            return ObjResponse::success($data);
        } catch (\Exception $ex) {
            Log::error("EmployeeController ~ snapshot: " . $ex->getMessage());
            return ObjResponse::serverError('Error al obtener snapshot', $ex);
        }
    }
}