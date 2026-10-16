<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'is_system', 'active'];
    protected $casts = ['is_system' => 'boolean', 'active' => 'boolean'];

    public function permissions() { return $this->belongsToMany(Permission::class); }
    public function users() { return $this->hasMany(User::class); }
}
