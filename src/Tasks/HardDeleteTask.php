<?php declare(strict_types = 1);

namespace Waterfall\Tasks;

use Illuminate\Support\Facades\DB;

class HardDeleteTask extends Task
{
    /**
     * Create the database query for the task.
     *
     * @internal.
     *
     */
    public function generate(mixed $id) : mixed
    {
        $query = DB::table($this->table)->where('id', $id);

        return static::attempt(fn() => $query->delete());
    }
}
