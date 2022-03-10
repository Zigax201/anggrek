<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class photouser extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'id_user',
        'path'
    ];
}
