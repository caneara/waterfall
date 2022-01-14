<?php declare(strict_types = 1);

namespace Waterfall\Tasks;

use Closure;
use Illuminate\Support\Facades\DB;
use Waterfall\Support\InteractsWithDatabase;

class Task
{
    use InteractsWithDatabase;

    /**
     * The closure to run on the query
     *
     */
    protected ?Closure $closure;

    /**
     * The foreign key.
     *
     */
    protected string $key;

    /**
     * The database table.
     *
     */
    protected string $table;

    /**
     * Constructor.
     *
     */
    public function __construct(string $table, string $key, Closure $query = null)
    {
        $this->key     = $key;
        $this->table   = $table;
        $this->closure = $query;
    }

    /**
     * Generate a new cascading deletion task.
     *
     */
    public static function create(string $type, string | Closure $key = null, Closure $query = null) : static
    {
        $table   = static::getTableFrom($type);
        $foreign = static::getForeignKeyFrom($key);
        $closure = $key instanceof Closure ? $key : $query;

        return new static($table, $foreign, $closure);
    }

    /**
     * Generate the database query for the task.
     *
     */
    public function query(mixed $id) : mixed
    {
        $closure = $this->closure;

        return DB::table($this->table)
            ->where($this->key, $id)
            ->when(filled($closure), fn($query) => $closure($query))
            ->limit(config('waterfall.batch_size'));
    }
}
