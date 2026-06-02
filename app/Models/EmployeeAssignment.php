<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class EmployeeAssignment extends Model
{
    use HasFactory, Notifiable, Auditable;

    protected $fillable = [
        'employee_id',
        'department_uuid',
        'position_uuid',
        'start_date',
        'end_date',
        'active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relación con departamento
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_uuid', 'uuid')
            ->where('start_date', '<=', $this->start_date)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $this->start_date);
            });
    }

    // Relación con el puesto (versión adecuada según la fecha de la asignación)
    // Para usarla correctamente, debemos pasar la fecha de la asignación.
    // Mejor obtener mediante un scope en consulta.
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_uuid', 'uuid')
            ->where('start_date', '<=', $this->start_date)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $this->start_date);
            });
    }

    public function scopeActiveAt($query, $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $date);
            });
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('end_date');
    }
}