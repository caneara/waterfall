<?php declare(strict_types = 1);

namespace Waterfall\Support;

use Closure;

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
}
