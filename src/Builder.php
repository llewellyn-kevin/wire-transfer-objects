<?php

namespace App\DataTransferObjects;

use Exception;
use Livewire\Wireable;

class Builder implements Wireable
{
    public function __construct(
        protected array $attributes = [],
        public ?string $dataObject = null,
    ) {
    }

    public function toData()
    {
        foreach ($this->attributes as $key => $attribute) {
            if ($attribute instanceof Builder) {
                $this->attributes[$key] = $attribute->toData();
            }
        }

        return call_user_func("{$this->dataObject}::from", $this->attributes);
    }

    public function toLivewire()
    {
        foreach ($this->attributes as $key => $attribute) {
            if ($attribute instanceof Builder) {
                $this->attributes[$key] = $attribute->toLivewire();
            }
        }
        return [...$this->attributes, 'lw-dataObject' => $this->dataObject];
    }

    public static function fromLivewire($values)
    {
        if (!key_exists('lw-dataObject', $values)) {
            return $values;
        }

        $dataObject = $values['lw-dataObject'];

        // foreach ($values as $key => $value) {
        //     if (is_array($value)) {
        //         $values[$key] =  Builder::fromLivewire($value);
        //     }
        // }

        return call_user_func("$dataObject::getBuilder", $values);
    }

    public function __get(string $key)
    {
        if (!$this->attributeExists($key)) {
            throw new Exception("Trying to access property '$$key', which does not exist on underlying data object '{$this->dataObject}'.");
        }

        return $this->attributes[$key] ?? '';
    }

    public function __set(string $key, mixed $value)
    {
        $this->attributes[$key] = $value;
    }

    private function attributeExists(string $attributeKey): bool
    {
        return key_exists($attributeKey, $this->attributes);
    }
}
