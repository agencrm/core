<?php
// app/Models/Flow.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flow extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'graph', 'status', 'published_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'graph' => 'array',
        'published_at' => 'datetime',
    ];
}
