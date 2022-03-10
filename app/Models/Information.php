<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Information extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_product',
        'id_catalog',
        'parameter',
        'value'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
}
