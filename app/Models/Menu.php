<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use Auditable;

    protected $fillable = ['parent_id', 'name', 'slug', 'route', 'icon', 'required_permission', 'sort_order', 'active'];
    protected $casts = ['active' => 'boolean'];
    public function parent() { return $this->belongsTo(Menu::class, 'parent_id'); }
    public function children() { return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort_order'); }
}
