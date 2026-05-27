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
    protected $versioned = true;
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