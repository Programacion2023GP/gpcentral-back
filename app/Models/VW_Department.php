<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class VW_Department extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'vw_departments';
    public $timestamps = false;
    protected $primaryKey = 'id';
}
