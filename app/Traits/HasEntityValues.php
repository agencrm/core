<?php

// app/Traits/HasEntityValues.php

namespace App\Traits;

use App\Models\EntityValue;

trait HasEntityValues
{
    public function entityValues()
    {
        return $this->morphMany(EntityValue::class, 'entityable');
    }

    public function getField(string $key): ?string
    {
        return $this->entityValues()
            ->where('type', 'field')
            ->where('key', $key)
            ->value('value');
    }

    public function getFields(): array
    {
        return $this->entityValues()
            ->where('type', 'field')
            ->pluck('value', 'key')
            ->toArray();
    }

    public function setField(string $key, mixed $value): EntityValue
    {
        return $this->entityValues()->updateOrCreate(
            ['type' => 'field', 'key' => $key],
            ['value' => is_scalar($value) ? (string) $value : json_encode($value)],
        );
    }

    public function clearField(string $key): bool
    {
        return $this->entityValues()
            ->where('type', 'field')
            ->where('key', $key)
            ->delete();
    }

    public function hasField(string $key): bool
    {
        return $this->entityValues()
            ->where('type', 'field')
            ->where('key', $key)
            ->exists();
    }

    public function setFields(array $fields): void
    {
        foreach ($fields as $key => $value) {
            $this->setField($key, $value);
        }
    }


    public function getLabels(): \Illuminate\Support\Collection
    {
        return $this->entityValues()
            ->where('type', 'label')
            ->pluck('value'); // assuming value = label ID
    }

    public function exportFields(): array
    {
        return $this->getFields(); // or mutate further if needed
    }

    

}
