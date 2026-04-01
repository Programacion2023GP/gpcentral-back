<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function accesses()
    {
        return $this->hasMany(EmployeeSystemAccess::class);
    }
}
