<?php declare(strict_types = 1);

namespace Waterfall\Support;

use Closure;
use Illuminate\Support\Str;

trait InteractsWithDatabase
{
    /**
     * Attempt to execute the given closure.
     *
     */
    protected static function attempt(Closure $closure) : mixed
    {
        return retry(40, fn() => $closure(), 250);
    }

    /**
     * Extract the foreign key from the given key.
     *
     */
    protected static function getForeignKeyFrom(string | Closure $key = null) : string
    {
        if (is_string($key) && filled($key)) {
            return $key;
        }

        $class = class_basename(debug_backtrace()[2]['object']::$type);

        return (string) Str::of($class)->lower()->singular()->append('_id');
    }

    /**
     * Extract the table name from the given type.
     *
     */
    protected static function getTableFrom(string $type) : string
    {
        return class_exists($type) ? (new $type())->getTable() : $type;
    }
}
