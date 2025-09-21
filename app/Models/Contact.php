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

    /**
     * Polymorphic relation: this contact can have many comments.
     */
    public function comments()
    {
        return $this->morphMany(\App\Models\Comment::class, 'commentable');
    }

    /**
     * Polymorphic relation: this contact can have many notes.
     */
    public function notes()
    {
        return $this->morphMany(\App\Models\Note::class, 'noteable');
    }

}
