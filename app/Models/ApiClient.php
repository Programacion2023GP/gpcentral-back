<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    protected $fillable = [
        'system_id',
        'name',
        'client_id',
        'secret_hash',
        'abilities',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'active',
    ];

    protected $hidden = ['secret_hash'];

    protected $casts = [
        'abilities' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function hasAbility(string $ability): bool
    {
        $abilities = $this->abilities ?? [];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function isUsable(): bool
    {
        return $this->active
            && is_null($this->revoked_at)
            && (is_null($this->expires_at) || $this->expires_at->isFuture())
            && (!$this->system || ($this->system->active && $this->system->is_active));
    }
}
