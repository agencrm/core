<?php

// app/Models/View.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class View extends Model
{
    protected $fillable = [
        'name',
        'view_type',
        'created_by',
        'viewable_type',
        'viewable_id',
        'is_default',
    ];

    public function attributes(): HasMany
    {
        return $this->hasMany(ViewAttribute::class);
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    // Convenience helpers
    public function getAttr(string $key, $default = null)
    {
        $attr = $this->attributes()->where('key', $key)->first();
        return $attr?->value_json ?? $attr?->value_string ?? $default;
    }

    public function setAttr(string $key, $value): self
    {
        $payload = ['value_json' => null, 'value_string' => null, 'value_number' => null, 'value_boolean' => null];

        if (is_array($value) || is_object($value)) {
            $payload['value_json'] = $value;
        } elseif (is_bool($value)) {
            $payload['value_boolean'] = $value;
            $payload['value_json'] = $value;
        } elseif (is_numeric($value)) {
            $payload['value_number'] = $value;
            $payload['value_json'] = $value + 0;
        } elseif (is_string($value)) {
            $payload['value_string'] = $value;
            $payload['value_json'] = $value;
        } else {
            $payload['value_json'] = $value; // fallback
        }

        $this->attributes()->updateOrCreate(['key' => $key], $payload);
        return $this;
    }
}
