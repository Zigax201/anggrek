<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class List_product_transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'id_transaction',
        'id_product',
        'id_spec',
        'qty'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
}
