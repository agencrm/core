<?php

// app/Models/Label.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'label_group_id',
        'name',
        'color',
        'sort_order',
    ];

    public function group()
    {
        return $this->belongsTo(LabelGroup::class, 'label_group_id');
    }
}
