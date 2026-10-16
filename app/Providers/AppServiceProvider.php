<?php

namespace App\Providers;

use App\Models\Administration;
use App\Models\CodigoPostal;
use App\Models\Community;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeDetail;
use App\Models\Estados;
use App\Models\Municipality;
use App\Models\Organization;
use App\Models\Perimeter;
use App\Models\Position;
use App\Models\Role;
use App\Models\Menu;
use App\Models\System;
use App\Models\User;
use App\Models\UserSystemAccess;
use App\Observers\GlobalModelObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Lista EXPLÍCITA de modelos a observar
     * Máximo rendimiento - sin reflection, sin filesystem
     */
    protected $observableModels = [
        Role::class,
        Menu::class,
        Administration::class,
        CodigoPostal::class,
        Community::class,
        Department::class,
        Employee::class,
        EmployeeAssignment::class,
        EmployeeDetail::class,
        Estados::class,
        Municipality::class,
        Organization::class,
        Perimeter::class,
        Position::class,
        System::class,
        User::class,
        UserSystemAccess::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $frontend = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return $frontend.'/restablecer-contrasena?token='.$token.'&email='.urlencode($user->email);
        });
        foreach ($this->observableModels as $model) {
            $model::observe(GlobalModelObserver::class);
        }
    }
}
