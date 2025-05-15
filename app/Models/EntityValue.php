<?php

// app/Models/EntityValue.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EntityValue extends Model
{
    protected $fillable = [
        'type',
        'key',
        'value',
    ];

    public function entityable()
    {
        return $this->morphTo();
    }

}
