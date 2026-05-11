<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Models\ObjResponse;
use App\Models\User;
use App\Models\VW_User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserController extends BaseCrudController
{
    protected $modelClass = User::class;
    protected $modelClassView = VW_User::class;
    protected $imageDirectory = 'users';
    // protected $imageFields = ['avatar', 'signature_image']; // Ajusta según tus campos
    protected $filterByRoleAuth = true;
    // protected $validationRules = [
    //     'employee_code' => 'required|string|max:50|unique:users,employee_code',
    //     'hire_date' => 'required|date',
    // ];
    // protected $validator = [
    //     [
    //         'field' => 'username',
    //         'label' => 'Nombre de usuario',
    //         'rules' => ['string'],
    //         'messages' => [
    //             'string' => 'El nombre de usuario debe ser texto.',
    //         ]
    //     ],
    //     [
    //         'field' => 'email',
    //         'label' => 'Correo electrónico',
    //         'rules' => ['email'],
    //         'messages' => [
    //             'email' => 'El correo electrónico no es válido.',
    //         ]
    //     ],
    //     [
    //         'field' => 'employee_id',
    //         'label' => 'Empleado',
    //         'rules' => [],
    //         'messages' => []
    //     ],
    // ];
    // protected $selectId = "id";
    protected $selectLabel = 'CONCAT(username, " - ", employee_code, " - ", full_name)'; // Si usas vista
    // protected $selectLabel = ['CONCAT(employee_code, " - ", name, " ", plast_name)']; // Si usas vista

    public function __construct()
    {
        // $this->indexQueryCallback = function ($query, Request $request) {
        //     $query->with(['currentDetail', 'currentAssignment.position.department.organization']);
        // };
    }

    /**
     * Mostrar lista de usuarios.
     *
     */
    // public function index(Request $request): JsonResponse
    // {
    //     ObjResponse::default();
    //     try {
    //         $roleAuth = Auth::user()->role_id;
    //         $list = VW_User::where("role_id", ">=", $roleAuth)
    //             ->orderBy('id', 'desc');
    //         // $list = User::with('role', 'employee')
    //         // ->where("role_id", ">=", $roleAuth)
    //         // ->orderBy('id', 'desc');
    //         if ($roleAuth > 1) $list = $list->where("active", true);
    //         $list = $list->get();

    //         return ObjResponse::success($list, 'Peticion satisfactoria | Lista de usuarios.');
    //         // $response->data["message"] = 'Peticion satisfactoria | Lista de usuarios.';
    //         // $response->data["result"] = $list;

    //         // Http::get(route('api.notifications'));
    //     } catch (\Exception $ex) {
    //         $msg = "UserController ~ index ~ Hubo un error -> " . $ex->getMessage();
    //         Log::error($msg);
    //         return ObjResponse::error($msg);
    //     }
    // }

    /**
     * Mostrar lista de usuarios activos por role
     * uniendo con roles.
     *
     * @return \Illuminate\Http\Response $response
     */
    // public function selectIndexByRole(Int $role_id)
    // {
    //     ObjResponse::default();
    //     try {
    //         $auth = Auth::user();
    //         Log::info($role_id);

    //         $signo = "=";
    //         $signo = $role_id == 2 && $auth->role_id == 1 ? "<=" : "=";

    //         $list = VW_User::where('active', true)->where("role_id", $signo, $role_id)
    //             ->select('id as id', "$this->selectLabel as label")
    //             ->orderBy('id', 'desc');

    //         if ($auth->role_id == 3) {
    //             $list = $list->where('id', $auth->id);
    //         }

    //         $list = $list->get();

    //         return ObjResponse::success();
    //         $response->data["message"] = 'peticion satisfactoria | lista de usuarios.';
    //         $response->data["alert_text"] = "usuarios encontrados";
    //         $response->data["result"] = $list;
    //     } catch (\Exception $ex) {
    //         $msg = "UserController ~ selectIndexByRole ~ Hubo un error -> " . $ex->getMessage();
    //         Log::error($msg);
    //         return ObjResponse::error($msg);
    //     }
    //     return response()->json($response, $response->data["status_code"]);
    // }



    /**
     * Crear o Actualizar usuario.
     *
     * @param \Illuminate\Http\Request $request
     * @param Int $id
     * 
     * @return \Illuminate\Http\Response $response
     */
    // public function createOrUpdate(Request $request)
    // {
    //     ObjResponse::default();
    //     try {
    //         $id = $request->id ?? null;

    //         $validator = $this->validateAvailableData($request, 'users', [
    //             [
    //                 'field' => 'username',
    //                 'label' => 'Nombre de usuario',
    //                 'rules' => ['string'],
    //                 'messages' => [
    //                     'string' => 'El nombre de usuario debe ser texto.',
    //                 ]
    //             ],
    //             [
    //                 'field' => 'email',
    //                 'label' => 'Correo electrónico',
    //                 'rules' => ['email'],
    //                 'messages' => [
    //                     'email' => 'El correo electrónico no es válido.',
    //                 ]
    //             ],
    //             [
    //                 'field' => 'employee_id',
    //                 'label' => 'Empleado',
    //                 'rules' => [],
    //                 'messages' => []
    //             ],
    //         ], $id);
    //         if ($validator->fails()) {
    //             $response->data["message"] = "Error de validación";
    //             $response->data["errors"] = $validator->errors();
    //             return response()->json($response);
    //         }

    //         $user = User::find($id);
    //         if (!$user) $user = new User();
    //         $user->fill($request->only(['email', 'username', 'password', 'role_id']));
    //         if ((int)$request->employee_id > 0) $user->employee_id = $request->employee_id;
    //         if ((bool)$request->changePassword && strlen($request->password) > 0) $user->password = Hash::make($request->password);
    //         $user->save();

    //         // $response->data = ObjResponse::success();
    //         // $response->data["message"] = $id > 0 ? 'peticion satisfactoria | usuario editado.' : 'peticion satisfactoria | usuario registrado.';
    //         // $response->data["alert_text"] = $id > 0 ? "Usuario editado" : "Usuario registrado";

    //         // $this->notificationPush($response->data["alert_text"],$response->data["alert_icon"]);
    //     } catch (\Exception $ex) {
    //         $msg = "UserController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
    //         Log::error($msg);
    //         return ObjResponse::error($msg);
    //     }


    //     // // crear notificación
    //     // $notification = Notification::create([
    //     //     'title' => 'Nueva Orden',
    //     //     'message' => 'Se creó una nueva orden: ' . $order->title,
    //     //     'type' => 'success',
    //     //     'created_by' => auth()->id(),
    //     // ]);

    //     // // asignar destinatarios (ejemplo: rol admin id=1)
    //     // $target = NotificationTarget::create([
    //     //     'notification_id' => $notification->id,
    //     //     'target_type' => 'role',
    //     //     'target_id' => 1,
    //     // ]);

    //     // // obtener usuarios del rol admin
    //     // $userIds = \App\Models\User::where('role_id', 1)->pluck('id')->toArray();

    //     // broadcast(new NewNotification($notification, $userIds))->toOthers();

    //     return response()->json($response, $response->data["status_code"]);
    // }
}
