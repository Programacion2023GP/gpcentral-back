<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class System extends Model
{
    use HasFactory, Notifiable, Auditable;

    protected $fillable = ['code', 'name', 'description', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function accesses()
    {
        return $this->hasMany(EmployeeSystemAccess::class);
    }
}