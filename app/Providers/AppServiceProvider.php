<?php

namespace App\Providers;

use App\Models\Administration;
use App\Models\CodigoPostal;
use App\Models\Community;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignments;
use App\Models\EmployeeDetails;
use App\Models\Estados;
use App\Models\Municipality;
use App\Models\Organization;
use App\Models\Perimeter;
use App\Models\Position;
use App\Models\System;
use App\Models\User;
use App\Models\UserSystemAccess;
use App\Observers\GlobalModelObserver;
use Illuminate\Support\ServiceProvider;

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
        // Role::class,
        // Menu::class,
        Administration::class,
        CodigoPostal::class,
        Community::class,
        Department::class,
        Employee::class,
        EmployeeAssignments::class,
        EmployeeDetails::class,
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
        foreach ($this->observableModels as $model) {
            $model::observe(GlobalModelObserver::class);
        }
    }
}
