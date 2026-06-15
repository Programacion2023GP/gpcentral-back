<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CodigoPostalController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EstadosController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSystemAccessController;
use App\Models\ObjResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/', function (Request $request) {
    return "API LARAVEL v1.0.0.0";
});

// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/signup', [AuthController::class, 'signup']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/verify-system-access', [UserSystemAccessController::class, 'verifyAccess']);
});

// Route::middleware('auth:sanctum')->group(function () {
Route::post('/checkLoggedIn', function (Response $response, Request $request) {
    $response->data = ObjResponse::success();
    $id = Auth::user()->id;
    if ($id < 1 || !$id) {
        throw ValidationException::withMessages([
            'message' => false
        ]);
    }
    if ($request->url) {
        $response->data = ObjResponse::default();
        try {
            $menu = Menu::where('url', $request->url)->where('active', 1)->select("id")->first();
            $response->data = ObjResponse::success();
            $response->data["message"] = 'Peticion satisfactoria | validar inicio de sesión.';
            $response->data["result"] = $menu;
        } catch (\Exception $ex) {
            $response->data = ObjResponse::error($ex->getMessage());
        }
        return response()->json($response, $response->data["status_code"]);
    }
    return response()->json($response, $response->data["status_code"]);
});
Route::get('/logout', [AuthController::class, 'logout']);
Route::post('/changePasswordAuth', [AuthController::class, 'changePasswordAuth']);

Route::prefix("logs")->group(function () {
    Route::post("/", [ActivityLogController::class, 'index']);
    Route::get("/dashboard", [ActivityLogController::class, 'dashboard']);
    Route::get("/export", [ActivityLogController::class, 'export']);
});

// Route::prefix("menus")->group(function () {
//     Route::get("/", [MenuController::class, 'index']);
//     Route::get("/getMenusByRole/{pages_read}", [MenuController::class, 'getMenusByRole']);
//     Route::get("/getHeadersMenusSelect", [MenuController::class, 'getHeadersMenusSelect']);
//     Route::get("/selectIndexToRoles", [MenuController::class, 'selectIndexToRoles']);
//     Route::post("/createOrUpdate", [MenuController::class, 'createOrUpdate']);
//     Route::get("/id/{id}", [MenuController::class, 'show']);
//     Route::get("/disEnable/{id}/{active}", [MenuController::class, 'disEnable']);

//     Route::post("/getIdByUrl", [MenuController::class, 'getIdByUrl']);
// });

// Route::prefix("roles")->group(function () {
//     Route::get("/", [RoleController::class, 'index']);
//     Route::get("/selectIndex", [RoleController::class, 'selectIndex']);
//     Route::post("/createOrUpdate", [RoleController::class, 'createOrUpdate']);
//     Route::get("/id/{id}", [RoleController::class, 'show']);
//     Route::delete("/delete", [RoleController::class, 'delete']);
//     Route::get("/disEnable/{id}/{active}", [RoleController::class, 'disEnable']);
//     Route::get("/deleteMultiple", [RoleController::class, 'deleteMultiple']);

//     Route::post("/updatePermissions", [RoleController::class, 'updatePermissions']);
// });

Route::prefix("administrations")->group(function () {
    Route::get("/", [AdministrationController::class, 'index']);
    Route::get("/selectIndex", [AdministrationController::class, 'selectIndex']);
    Route::post("/createOrUpdate", [AdministrationController::class, 'createOrUpdate']);
    Route::get("/id/{id}", [AdministrationController::class, 'show']);
    Route::delete("/delete", [AdministrationController::class, 'delete']);
    Route::get("/disEnable/{id}/{active}", [AdministrationController::class, 'disEnable']);
    Route::get("/deleteMultiple", [AdministrationController::class, 'deleteMultiple']);
});

Route::prefix("organizations")->group(function () {
    Route::get("/", [OrganizationController::class, 'index']);
    Route::get("/selectIndex", [OrganizationController::class, 'selectIndex']);
    Route::post("/createOrUpdate", [OrganizationController::class, 'createOrUpdate']);
    Route::get("/id/{id}", [OrganizationController::class, 'show']);
    Route::delete("/delete", [OrganizationController::class, 'delete']);
    Route::get("/disEnable/{id}/{active}", [OrganizationController::class, 'disEnable']);
    Route::get("/deleteMultiple", [OrganizationController::class, 'deleteMultiple']);
});

Route::prefix("departments")->group(function () {
    Route::get("/", [DepartmentController::class, 'index']);
    Route::get("/selectIndex", [DepartmentController::class, 'selectIndex']);
    Route::post("/createOrUpdate", [DepartmentController::class, 'createOrUpdate']);
    Route::get("/id/{id}", [DepartmentController::class, 'show']);
    Route::delete("/delete", [DepartmentController::class, 'delete']);
    Route::get("/disEnable/{id}/{active}", [DepartmentController::class, 'disEnable']);
    Route::get("/deleteMultiple", [DepartmentController::class, 'deleteMultiple']);
    Route::post("directors", [DepartmentController::class, 'directors']);
});

Route::prefix("positions")->group(function () {
    Route::get("/", [PositionController::class, 'index']);
    Route::get("/selectIndex", [PositionController::class, 'selectIndex']);
    Route::post("/createOrUpdate", [PositionController::class, 'createOrUpdate']);
    Route::get("/id/{id}", [PositionController::class, 'show']);
    Route::delete("/delete", [PositionController::class, 'delete']);
    Route::get("/disEnable/{id}/{active}", [PositionController::class, 'disEnable']);
    Route::get("/deleteMultiple", [PositionController::class, 'deleteMultiple']);
});

Route::prefix("employees")->group(function () {
    Route::get("/", [EmployeeController::class, 'index']);
    Route::get("/selectIndex", [EmployeeController::class, 'selectIndex']);
    Route::post("/createOrUpdate", [EmployeeController::class, 'createOrUpdate']);
    Route::get("/id/{id}", [EmployeeController::class, 'show']);
    Route::get("/getEmployeeBy/{field}/{value}", [EmployeeController::class, 'getEmployeeBy']);
    Route::delete("/delete", [EmployeeController::class, 'delete']);
    Route::get("/disEnable/{id}/{active}", [EmployeeController::class, 'disEnable']);
    Route::get("/deleteMultiple", [EmployeeController::class, 'deleteMultiple']);
    Route::get("directors", [EmployeeController::class, 'directors']);
    Route::post("/change-director-assignment", [EmployeeController::class, 'changeDirectorAssignment']);
});

Route::prefix("users")->group(function () {
    Route::get("/", [UserController::class, 'index']);
    Route::get("/selectIndexByRole/{role_id}", [UserController::class, 'selectIndexByRole']);
    Route::get("/selectIndex", [UserController::class, 'selectIndex']);
    Route::post("/createOrUpdate", [UserController::class, 'createOrUpdate']);
    Route::get("/id/{id}", [UserController::class, 'show']);
    Route::delete("/delete", [UserController::class, 'delete']);
    Route::get("/disEnable/{id}/{active}", [UserController::class, 'disEnable']);
    Route::get("/deleteMultiple", [UserController::class, 'deleteMultiple']);
});

Route::prefix("user-system-access")->group(function () {
    // ... otras rutas

    // Accesos a sistemas
    Route::apiResource('/', UserSystemAccessController::class);
    Route::get('my-accesses', [UserSystemAccessController::class, 'myAccesses']);
});



    // ----------------- RUTAS BASICAS -----------------
// });

// Route::post('/notifications', [NotificationController::class, 'store'])->middleware('auth:sanctum');

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::get('cp/{cp}', [CodigoPostalController::class, 'index']);
// Route::get('cp/colonia/{id}', [CodigoPostalController::class, 'colonia']);

// Route::get('comunidades', [CodigoPostalController::class, 'indexCommunities']);
// Route::get('comunidades/municipio/{municipio_id}', [CodigoPostalController::class, 'indexCommunities']);
// Route::get('comunidades/id/{id}', [CodigoPostalController::class, 'showCommunity']);
// Route::post('comunidades/create', [CodigoPostalController::class, 'createOrUpdateCommunity']);
// Route::post('comunidades/update/{id}', [CodigoPostalController::class, 'createOrUpdateCommunity']);

// Route::get('comunidades/perimetro/{perimeter_id}', [CodigoPostalController::class, 'communitiesByPerimeter']);
// Route::get('colonias/perimetro/{perimeter_id}', [CodigoPostalController::class, 'coloniesByPerimeter']);

// Route::get('perimetros/id/{id?}', [CodigoPostalController::class, 'perimeters']);
// Route::get('perimetros/{perimeter_id}/assignToCommunity/{community_id}', [CodigoPostalController::class, 'assignPerimeterToCommunity']);
// Route::get('perimetros/selectIndex', [CodigoPostalController::class, 'selectIndexPerimeters']);
// Route::post('perimetros/create', [CodigoPostalController::class, 'createOrUpdatePerimeter']);
// Route::post('perimetros/update/{id}', [CodigoPostalController::class, 'createOrUpdatePerimeter']);

// Route::get('types/selectIndex', [CodigoPostalController::class, 'selectIndexTypesCommunity']);

// Route::prefix('gpd')->group(function () {
//     Route::get('cp/{cp}', [CodigoPostalController::class, 'indexGPD']);
//     Route::get('cp/colonia/{id}', [CodigoPostalController::class, 'showCommunityGPD']);
//     Route::get('comunidades', [CodigoPostalController::class, 'indexCommunitiesGPD']);
//     Route::get('comunidades/municipio/{municipio_id}', [CodigoPostalController::class, 'indexCommunitiesGPD']);
//     Route::get('comunidades/id/{id}', [CodigoPostalController::class, 'showCommunityGPD']);
//     Route::get('comunidades/perimetro/{perimeter_id}', [CodigoPostalController::class, 'communitiesGPDByPerimeter']);
// });
// Route::get('estados', [EstadosController::class, 'index']);
// Route::get('estados/{id}', [EstadosController::class, 'estadosFind']);


// Route::prefix('departamentos')->group(function () {
//     // Route::get('/', function () {
//     //     return 'Departamentos';
//     // });
//     include_once "departamentos.routes.php";
// });