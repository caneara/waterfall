<?php declare(strict_types = 1);

namespace Waterfall\Tasks;

use Illuminate\Support\Facades\DB;

class SoftDeleteTask extends Task
{
    /**
     * Generate the database query for the task.
     *
     */
    public function query(mixed $id) : mixed
    {
        $payload = [
            'deleted_at' => now(),
            'updated_at' => now(),
        ];

        $query = DB::table($this->table)->where('id', $id);

        return static::attempt(fn() => $query->update($payload));
    }
}
