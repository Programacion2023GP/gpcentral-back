<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Municipality extends Model
{
    use Notifiable, Auditable;

    protected $table = 'municipalities';
    public $timestamps = false;
}