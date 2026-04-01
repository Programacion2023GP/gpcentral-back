<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class VW_Employee extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'vw_employees';
    public $timestamps = false;
    protected $primaryKey = 'employee_id';
}
