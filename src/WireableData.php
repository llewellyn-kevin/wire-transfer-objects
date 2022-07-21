<?php

namespace LlewellynKevin\WireTransferObjects;

use Exception;
use Illuminate\Support\Arr;
use Livewire\Wireable;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelData\Data as SpatieData;

class WireableData extends SpatieData implements Wireable
{
    public function toLivewire()
    {
        return $this->toArray();
    }

    public static function fromLivewire($value)
    {
        return self::from($value);
    }

    public static function getBuilder(array $defaultValues = [])
    {
        return new Builder(static::nil($defaultValues), static::class);
    }

    /**
     * Return an empty data object, but with every nested data object filled with a builder.
     */
    private static function nil(array $defaultValues = []): array
    {
        $empty = static::empty($defaultValues);

        $reflection = new ReflectionClass(get_called_class());
        $properties = collect($reflection->getProperties());

        $properties->each(function (ReflectionProperty $property) use (&$empty, $defaultValues) {
            if (!$property->hasType() || $property->getType()->isBuiltin()) {
                return;
            }

            $relationship = new ReflectionClass($property->getType()->getName());
            $relationshipName = $relationship->getName();
            $relationshipParent = $relationship->getParentClass();
            if (!$relationshipParent) {
                return;
            }
            if ($relationshipParent->getName() === WireableData::class) { // Buildable
                data_set(
                    $empty,
                    $property->getName(),
                    call_user_func(
                        $relationshipName . "::getBuilder",
                        static::getNestedDefaults($property->getName(), $defaultValues)
                    ),
                );
            }
        });

        return $empty;
    }

    /**
     * If defaults are provided for nested builders, grab those defaults without the
     * property prefix. For example, if builder team is instantiated with default
     * owner.id set, default will be returned with an id to use in that build.
     */
    private static function getNestedDefaults(string $property, array $defaults): array
    {
        $bucket = [];

        foreach ($defaults as $key => $default) {
            if ($key === $property && is_array($default)) {
                return $default;
            }

            if (explode('.', $key)[0] === $property) {
                $fields = explode("$property.", $key);
                $bucket[end($fields)] = $default;
            }
        }

        return $bucket;
    }

    /**
     * Add an override for accessors to work.
     */
    public function __get(string $name): mixed
    {
        $studlyName = implode('', Arr::map(explode('_', $name), fn (string $field) => ucfirst($field)));
        $accessor =  "get{$studlyName}Attribute";
        if (method_exists($this, $accessor)) {
            return call_user_func([$this, $accessor]);
        }

        $classname = static::class;
        throw new Exception("Trying to access invalid property `$name`, which has no accessor or property defined on dto `$classname`");
    }
}
