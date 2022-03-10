<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class list_catalog_product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id',
        'id_product',
        'id_catalog',
        'id_parent'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
}
