<?php

namespace PrestaShop\FacetedSearch\QueryBuilder;

use ReflectionClass;

abstract class AbstractMappable implements MappableInterface
{
    public function map(callable $cb)
    {
        $recurseCb = function ($value) use ($cb) {
            if ($value instanceof MappableInterface) {
                return $cb($value->map($cb));
            } else {
                return $cb($value);
            }
        };

        $mapped = clone $this;

        $refl = new ReflectionClass($this);

        foreach ($refl->getProperties() as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($this);
            if ($value) {
                if (is_array($value)) {
                    $prop->setValue($mapped, array_map($recurseCb, $value));
                } else {
                    $prop->setValue($mapped, $recurseCb($value));
                }
            }
        }

        return $mapped;
    }
}
