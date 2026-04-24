<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Organization extends Model
{
    use HasFactory, Notifiable, Auditable;


    protected $fillable = ['code', 'name', 'active'];

    protected $casts = ['active' => 'boolean'];

    // Relación con departments (versiones actuales)
    public function departments()
    {
        return $this->hasMany(Department::class)->whereNull('end_date');
    }

    // Relación con todas las versiones de departamentos
    public function departmentVersions()
    {
        return $this->hasMany(Department::class);
    }
}
