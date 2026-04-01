<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'username',
        'email',
        'password',
        // 'role_id',
        'active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
    ];


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // public function role()
    // {
    //     return $this->belongsTo(Role::class);
    // }

    // Accesos a sistemas a través del empleado (versiones actuales)
    public function systemAccesses()
    {
        return $this->hasMany(UserSystemAccess::class)->whereNull('end_date');
    }
    // public function systemAccesses()
    // {
    //     return $this->hasManyThrough(
    //         EmployeeSystemAccess::class,
    //         Employee::class,
    //         'id',          // foreign key on employees
    //         'employee_id', // foreign key on employee_system_access
    //         'employee_id', // local key on users
    //         'id'           // local key on employees
    //     )->whereNull('employee_system_access.end_date');
    // }

    // Todos los accesos históricos
    public function allSystemAccesses()
    {
        return $this->hasMany(UserSystemAccess::class);
    }

    // Permisos (rol) en un sistema específico (método de ayuda)
    // public function roleInSystem(string $systemCode)
    // {
    //     return $this->systemAccesses()
    //         ->whereHas('system', fn($q) => $q->where('code', $systemCode))
    //         ->value('role');
    // }

    // Helper para saber si tiene acceso a un sistema (activo en la fecha actual)
    public function hasAccessToSystem(string $systemCode, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();
        return $this->allSystemAccesses()
            ->whereHas('system', fn($q) => $q->where('code', $systemCode))
            ->activeAt($date)
            ->exists();
    }
}