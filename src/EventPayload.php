<?php

namespace MVF\Servicer;

use function Functional\map;

trait EventPayload
{
    /**
     * Constructs array of object's attributes and values and transforms attributes to snake case.
     *
     * @return array
     */
    public function toPayload(): array
    {
        $attributes = get_object_vars($this);
        $keys = map(array_keys($attributes), $this->transformToSnakeCase());
        $values = map(array_values($attributes), $this->transformToPayload());

        return array_combine($keys, $values);
    }

    /**
     * Higher order function that takes a string and converts it to snake case.
     *
     * @return callable
     */
    private function transformToSnakeCase(): callable
    {
        return function ($key) {
            return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
        };
    }

    /**
     * Higher order function that converts attribute values to either values or associative arrays.
     *
     * @return callable
     */
    private function transformToPayload(): callable
    {
        return function ($value) {
            if ($value instanceof EventPayloadInterface) {
                return $value->toPayload();
            }

            return $value;
        };
    }
}
