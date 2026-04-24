<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Department extends Model
{
    use HasFactory, Notifiable, Auditable;

    protected $fillable = [
        'uuid',
        'code',
        'organization_id',
        'name',
        'seal_image',
        'start_date',
        'end_date',
        'active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
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

    // Relación con la organización
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // Relación con puestos (versiones actuales que referencian este departamento)
    public function positions()
    {
        return $this->hasMany(Position::class, 'department_uuid', 'uuid')
            ->whereNull('end_date');
    }

    // Scope para obtener la versión activa en una fecha
    public function scopeActiveAt($query, $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $date);
            });
    }

    // Obtener la versión actual (end_date null)
    public function scopeCurrent($query)
    {
        return $query->whereNull('end_date');
    }
}