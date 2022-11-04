<?php declare(strict_types = 1);

namespace Waterfall\Tasks;

use Closure;
use ArrayAccess;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Waterfall\Support\InteractsWithArray;
use Waterfall\Support\InteractsWithDatabase;

class Task implements ArrayAccess
{
    use InteractsWithArray,
        InteractsWithDatabase;

    /**
     * The closure to execute after the delete operation.
     *
     */
    public ?Closure $after;

    /**
     * The maximum number of records to include in a batch.
     *
     */
    public ?int $batch;

    /**
     * The closure to execute before the delete operation.
     *
     */
    public ?Closure $before;

    /**
     * Flag for whether to hydrate the records.
     *
     */
    public bool $hydrate;

    /**
     * The foreign key.
     *
     */
    protected string $key;

    /**
     * The Eloquent model.
     *
     */
    public string $model;

    /**
     * The closure to execute on the database query.
     *
     */
    protected ?Closure $query;

    /**
     * The rest time to wait between jobs.
     *
     */
    public ?int $rest;

    /**
     * The database table.
     *
     */
    protected string $table;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $this->after   = null;
        $this->batch   = null;
        $this->before  = null;
        $this->hydrate = false;
        $this->key     = '';
        $this->model   = '';
        $this->query   = null;
        $this->rest    = null;
        $this->table   = '';
    }

    /**
     * Set the closure that should be executed after the delete operation.
     *
     */
    public function after(Closure $hook) : static
    {
        $this->after = $hook;

        return $this;
    }

    /**
     * Set the maximum number of records to include in a batch.
     *
     */
    public function batch(int $total) : static
    {
        $this->batch = $total;

        return $this;
    }

    /**
     * Set the closure that should be executed before the delete operation.
     *
     */
    public function before(Closure $hook) : static
    {
        $this->before = $hook;

        return $this;
    }

    /**
     * Generate a new cascading deletion task.
     *
     */
    public static function create() : static
    {
        return new static();
    }

    /**
     * Create the database query for the task.
     *
     * @internal.
     *
     */
    public function generate(mixed $id) : mixed
    {
        return DB::table($this->table)
            ->where($this->guessKey(), $id)
            ->when(filled($this->query), fn ($query) => $this['query']($query))
            ->limit($this->batch ?? config('waterfall.batch_size'));
    }

    /**
     * Retrieve the foreign key that should be used.
     *
     */
    protected function guessKey() : string
    {
        if (filled($this->key)) {
            return $this->key;
        }

        $class = class_basename(debug_backtrace()[2]['object']::$type);

        return (string) Str::of($class)->lower()->singular()->append('_id');
    }

    /**
     * Set whether the records accessed via hooks should be converted to models.
     *
     */
    public function hydrate() : static
    {
        $this->hydrate = true;

        return $this;
    }

    /**
     * Set the foreign key that links the relation to the main model.
     *
     */
    public function key(string $name) : static
    {
        $this->key = $name;

        return $this;
    }

    /**
     * Set the model that the task should run upon.
     *
     */
    public function model(string $class) : static
    {
        $this->model = $class;

        return $this->table((new $class())->getTable());
    }

    /**
     * Set the closure that should be executed on the database query.
     *
     */
    public function query(Closure $query) : static
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set the delay time to wait between jobs.
     *
     */
    public function rest(mixed $seconds) : static
    {
        $this->rest = is_int($seconds) ? $seconds : $seconds->endOfSecond()->diffInSeconds(now());

        return $this;
    }

    /**
     * Set the table that the task should run upon.
     *
     */
    public function table(string $name) : static
    {
        $this->table = $name;

        return $this;
    }
}
