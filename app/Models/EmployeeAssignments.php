<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAssignments extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
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
