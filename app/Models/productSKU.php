<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productSKU extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'id_product',
        'sku_code'
    ];
    
    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
}

