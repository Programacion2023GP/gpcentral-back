<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class UserSystemAccess extends Model
{
    use HasFactory, Notifiable, Auditable;

    protected $table = 'user_system_access';

    protected $fillable = [
        'user_id',
        'system_id',
        // 'role',
        'start_date',
        'end_date',
        'active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    // public function employee()
    // {
    //     return $this->belongsTo(Employee::class);
    // }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    // Scope para vigencia en una fecha
    public function scopeActiveAt($query, $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>', $date);
            });
    }

    // Scope para versión actual
    public function scopeCurrent($query)
    {
        return $query->whereNull('end_date');
    }
}
