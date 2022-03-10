<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'id_user',
        'number',
        'total_price',
        'payment_status',
        'kurir',
        'address',
        'snap_token',
        'timestamp'
    ];

    // protected $hidden = [
    //    'snap_token'
    // ];
}
