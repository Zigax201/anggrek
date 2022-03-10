<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'id_user',
        'id_product',
        'id_spec',
        'qty'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];

    // protected $hidden = [
    //     'id'
    // ];
}
