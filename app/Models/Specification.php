<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specification extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_product',
        'name_spec',
        'base_price',
        'publish_price'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
}
