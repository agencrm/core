<?php

// app/Models/ViewAttribute.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewAttribute extends Model
{
    protected $fillable = [
        'view_id',
        'key',
        'value_json',
        'value_string',
        'value_number',
        'value_boolean',
    ];

    protected $casts = [
        'value_json' => 'array',
        'value_boolean' => 'boolean',
        'value_number' => 'integer',
    ];

    public function view(): BelongsTo
    {
        return $this->belongsTo(View::class);
    }
}
