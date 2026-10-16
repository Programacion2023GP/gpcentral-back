<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    protected $fillable = ['requested_by', 'source', 'trigger', 'status', 'source_hash', 'summary', 'error_message', 'started_at', 'completed_at'];
    protected $casts = ['summary' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    public function changes() { return $this->hasMany(SyncChange::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
}
