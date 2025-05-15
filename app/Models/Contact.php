<?php

// app/Models/Contact.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasEntityValues;

class Contact extends Model
{
    use HasFactory, HasEntityValues; 

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'notes',
    ];

    

}
