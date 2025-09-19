<?php

// app/Models/Form.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasEntityValues;
// use Illuminate\Database\Eloquent\SoftDeletes; // uncomment if you use soft deletes

class Form extends Model
{
    use HasFactory, HasEntityValues; 
    // use SoftDeletes;


    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'label_id',
    ];

    protected $casts = [
        // add casts if you later store JSON fields, booleans, etc.
    ];
}
