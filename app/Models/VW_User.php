<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class VW_User extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'vw_users';
    public $timestamps = false;
    protected $primaryKey = 'user_id'; // opcional
    // No se permiten inserciones/actualizaciones
}
