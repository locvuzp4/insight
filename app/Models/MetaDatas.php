<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaDatas extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_data'
    ];

    protected $casts = [
        'meta_data' => 'array'
    ];
}
