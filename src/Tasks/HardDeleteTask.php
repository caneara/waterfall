<?php declare(strict_types = 1);

namespace Waterfall\Tasks;

use Illuminate\Support\Facades\DB;

class HardDeleteTask extends Task
{
    /**
     * Generate the database query for the task.
     *
     */
    public function query(mixed $id) : mixed
    {
        $query = DB::table($this->table)->where('id', $id);

        return static::attempt(fn() => $query->delete());
    }
}
