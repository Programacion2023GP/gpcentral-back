<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['employee_code', 'hire_date', 'active'];

    protected $casts = [
        'hire_date' => 'date',
        'active' => 'boolean',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    // Relación con detalles (versiones actuales)
    public function currentDetail()
    {
        return $this->hasOne(EmployeeDetail::class)->whereNull('end_date');
    }

    // Todos los detalles (versiones)
    public function details()
    {
        return $this->hasMany(EmployeeDetail::class);
    }

    // Asignaciones actuales
    public function currentAssignment()
    {
        return $this->hasOne(EmployeeAssignment::class)->whereNull('end_date');
    }

    // Todas las asignaciones
    public function assignments()
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    // Accesos a sistemas actuales
    public function currentSystemAccesses()
    {
        return $this->hasMany(EmployeeSystemAccess::class)->whereNull('end_date');
    }

    public function systemAccesses()
    {
        return $this->hasMany(EmployeeSystemAccess::class);
    }
}
