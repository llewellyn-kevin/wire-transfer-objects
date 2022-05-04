<?php

namespace App\Traits;

use App\DataTransferObjects\Builder;
use App\DataTransferObjects\WireableData;
use Error;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelData\Resolvers\DataValidatorResolver;

trait ValidatesDataObjects
{
    public function mountValidatesDataObjects()
    {
        if (method_exists($this, 'rules')) {
            // Don't autoload rules if they are overridden. Saves on resources per boot.
            return;
        }

        $reflection = new ReflectionClass($this);
        $properties = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC));

        $properties
            ->mapWithKeys(function (ReflectionProperty $property) {
                $nil = [0 => null];

                if (!$property->hasType() || $property->getType()->isBuiltin()) {
                    return $nil;
                }

                $className = $property->getType()?->getName();
                $propertyName = $property->getName();

                return $className !== null && $className !== '' ?
                    [$propertyName => new ReflectionClass($className)] :
                    $nil;
            })
            ->filter(fn (?ReflectionClass $reflectionClass) => isset($reflectionClass))
            ->filter(fn (ReflectionClass $reflectionClass) => $this->isAutodiscoverable($reflectionClass))
            ->each(function (ReflectionClass $class, string $propertyName) {
                try {
                    $className = $this->isBuilder($class) ? $this->$propertyName?->dataObject : $class->getName();
                } catch (Error $e) {
                    if (preg_match('/must not be accessed before initialization/', $e->getMessage())) {
                        throw new Error("Typed properties must be initialized in the mount method or have a default value. Property '$$propertyName' was accessed before initialization while trying to autoload rules.");
                    } else {
                        throw $e;
                    }
                }

                if ($className === '' || is_null($className)) {
                    return;
                }

                $this->rules = array_merge(
                    $this->getDtoRules([$propertyName => $className]),
                    $this->rules ?? [],
                );
            });
    }

    public function getDtoRules(array $rules): array
    {
        $bucket = [];
        collect($rules)->each(function (string $class, string $propertyName) use (&$bucket) {
            collect(app(DataValidatorResolver::class)->execute($class, [])->getRules())
                ->each(
                    function ($rule, $key) use (&$bucket, $propertyName) {
                        $bucket["$propertyName.$key"] = $rule;
                    }
                );
        });
        return $bucket;
    }

    private function isDataClass(ReflectionClass $class): bool
    {
        return $class->getParentClass() && $class->getParentClass()?->getName() === WireableData::class;
    }

    private function isBuilder(ReflectionClass $class): bool
    {
        return $class->getName() === Builder::class;
    }

    private function isAutodiscoverable(ReflectionClass $class): bool
    {
        return $this->isDataClass($class) || $this->isBuilder($class);
    }
}
