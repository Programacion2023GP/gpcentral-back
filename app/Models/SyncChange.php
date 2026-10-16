<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncChange extends Model
{
    protected $fillable = ['sync_run_id', 'employee_id', 'employee_code', 'change_type', 'incoming_data', 'current_data', 'differences', 'selected', 'applied_at'];
    protected $casts = ['incoming_data' => 'array', 'current_data' => 'array', 'differences' => 'array', 'selected' => 'boolean', 'applied_at' => 'datetime'];
    public function run() { return $this->belongsTo(SyncRun::class, 'sync_run_id'); }
    public function employee() { return $this->belongsTo(Employee::class); }
}
