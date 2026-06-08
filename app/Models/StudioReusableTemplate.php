<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioReusableTemplate extends Model
{
    protected $table = 'studio_reusable_templates';

    protected $fillable = [
        'name',
        'type', // mapping, transform
        'config'
    ];

    protected $casts = [
        'config' => 'array'
    ];
}
