<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CodigoPostalController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDetailController;
use App\Http\Controllers\EmployeeSyncController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSystemAccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => ['name' => 'GP Central API', 'version' => '1.1.0.0']);

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePasswordAuth']);
    Route::get('/navigation', [MenuController::class, 'navigation']);
    Route::get('/verify-system-access', [UserSystemAccessController::class, 'verifyAccess']);

    Route::prefix('logs')->middleware('permission:logs.view')->group(function () {
        Route::post('/', [ActivityLogController::class, 'index']);
        Route::get('/dashboard', [ActivityLogController::class, 'dashboard']);
        Route::get('/export', [ActivityLogController::class, 'export'])->middleware('permission:logs.export');
    });

    $crud = function (string $prefix, string $controller, string $permission) {
        Route::prefix($prefix)->group(function () use ($controller, $permission) {
            Route::get('/', [$controller, 'index'])->middleware("permission:$permission.view");
            Route::get('/selectIndex', [$controller, 'selectIndex'])->middleware("permission:$permission.view");
            Route::get('/id/{id}', [$controller, 'show'])->middleware("permission:$permission.view");
            Route::post('/createOrUpdate', [$controller, 'createOrUpdate'])->middleware("permission:$permission.update");
            Route::delete('/delete', [$controller, 'delete'])->middleware("permission:$permission.delete");
            Route::patch('/status/{id}/{action}', [$controller, 'disEnable'])->middleware("permission:$permission.update");
            Route::delete('/deleteMultiple', [$controller, 'deleteMultiple'])->middleware("permission:$permission.delete");
        });
    };

    $crud('administrations', AdministrationController::class, 'administrations');
    $crud('organizations', OrganizationController::class, 'organizations');
    $crud('departments', DepartmentController::class, 'departments');
    $crud('positions', PositionController::class, 'positions');
    $crud('employees', EmployeeController::class, 'employees');
    $crud('users', UserController::class, 'users');
    $crud('roles', RoleController::class, 'roles');
    $crud('menus', MenuController::class, 'menus');

    Route::get('/roles/permission-matrix', [RoleController::class, 'permissionMatrix'])->middleware('permission:roles.view');
    Route::post('/departments/directors', [DepartmentController::class, 'directors'])->middleware('permission:departments.view');
    Route::get('/employees/directors', [EmployeeController::class, 'directors'])->middleware('permission:employees.view');
    Route::post('/employees/change-director-assignment', [EmployeeController::class, 'changeDirectorAssignment'])->middleware('permission:employees.update');
    Route::get('/employees/getBy/{field}/{value}', [EmployeeController::class, 'getBy'])->middleware('permission:employees.view');
    Route::get('/employee-details/selectIndex', [EmployeeDetailController::class, 'selectIndex'])->middleware('permission:employees.view');
    Route::post('/employee-sync/preview', [EmployeeSyncController::class, 'preview'])->middleware('permission:sync.create');
    Route::get('/employee-sync/{run}', [EmployeeSyncController::class, 'show'])->middleware('permission:sync.view');
    Route::post('/employee-sync/{run}/apply', [EmployeeSyncController::class, 'apply'])->middleware('permission:sync.update');

    Route::prefix('user-system-access')->group(function () {
        Route::get('/', [UserSystemAccessController::class, 'index'])->middleware('permission:users.view');
        Route::post('/createOrUpdate', [UserSystemAccessController::class, 'createOrUpdate'])->middleware('permission:users.update');
        Route::delete('/delete', [UserSystemAccessController::class, 'delete'])->middleware('permission:users.delete');
        Route::get('/my-accesses', [UserSystemAccessController::class, 'myAccesses']);
    });

    Route::get('/postal-codes/{postalCode}', [CodigoPostalController::class, 'index'])->middleware('permission:postal-codes.view');
});
