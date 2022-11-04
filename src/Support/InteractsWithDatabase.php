<?php declare(strict_types = 1);

namespace Waterfall\Support;

use Closure;
use Exception;
use Illuminate\Support\Facades\DB;

trait InteractsWithDatabase
{
    /**
     * Attempt to execute the given closure.
     *
     */
    protected static function attempt(Closure $closure) : mixed
    {
        return retry(40, fn () => $closure(), 250);
    }

    /**
     * Determine if the database is not available.
     *
     */
    protected static function unavailable() : bool
    {
        try {
            return ! ! ! DB::connection()->getPdo();
        } catch (Exception) {
            return true;
        }
    }
}
