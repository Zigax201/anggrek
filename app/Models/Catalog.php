<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name'
    ];

    protected $hidden = [
        'created_at', 
        'updated_at'
    ];
}
