<?php declare(strict_types = 1);

namespace Waterfall\Support;

trait InteractsWithArray
{
    /**
     * Determine if the given attribute exists.
     *
     */
    public function offsetExists(mixed $offset) : bool
    {
        return property_exists($this, $offset);
    }

    /**
     * Retrieve the value for a given offset.
     *
     */
    public function offsetGet($offset) : mixed
    {
        return $this->{$offset};
    }

    /**
     * Assign the value for a given offset.
     *
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    /**
     * Clear the value for a given offset.
     *
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->{$offset});
    }
}
