<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class EmployeeDetail extends Model
{
    use HasFactory, Notifiable, Auditable;

    protected $fillable = [
        'employee_id',
        'avatar',
        'name',
        'plast_name',
        'mlast_name',
        'rfc',
        'curp',
        'sex',
        'phone',
        'signature_image',
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