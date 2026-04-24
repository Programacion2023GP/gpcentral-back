<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Community extends Model
{
    use  Notifiable, Auditable;
    
    protected $table = 'communities';
    // protected $primaryKey = 'id';
    // protected $fillable = [
    //     'name',
    //     'postalCode',
    //     'type',
    //     'zone',
    //     'municipalities_id',
    //     'perimeter_id'
    // ];
    public $timestamps = false;
}