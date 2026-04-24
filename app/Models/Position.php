<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Position extends Model
{
    use HasFactory, Notifiable, Auditable;

    protected $fillable = [
        'uuid',
        'organization_id',
        'department_uuid',
        'name',
        'parent_position_uuid',
        'start_date',
        'end_date',
        'active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // Relación con el departamento (versión correspondiente)
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_uuid', 'uuid')
            ->where('start_date', '<=', $this->start_date) // O usar un scope con fecha?
            // Nota: esta relación simple asume que el departamento tiene una versión actual.
            // Para consultas históricas es mejor usar un scope y fecha explícita.
            ->whereNull('end_date');
    }

    public function parentPosition()
    {
        return $this->belongsTo(Position::class, 'parent_position_uuid', 'uuid');
    }

    public function childPositions()
    {
        return $this->hasMany(Position::class, 'parent_position_uuid', 'uuid');
    }

    // Scope para activo en fecha
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
