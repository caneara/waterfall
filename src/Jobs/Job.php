<?php declare(strict_types = 1);

namespace Waterfall\Jobs;

use Illuminate\Bus\Queueable;
use Waterfall\Tasks\HardDeleteTask;
use Waterfall\Tasks\SoftDeleteTask;
use Illuminate\Database\Query\Builder;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Waterfall\Support\InteractsWithDatabase;

abstract class Job implements ShouldQueue
{
    use Queueable,
        Dispatchable,
        SerializesModels,
        InteractsWithQueue,
        InteractsWithDatabase;

    /**
     * The model identifier.
     *
     */
    protected mixed $id;

    /**
     * The current deletion task.
     *
     */
    protected int $task;

    /**
     * The number of seconds the job can run before timing out.
     *
     */
    public int $timeout = 60;

    /**
     * The number of times the job may be attempted.
     *
     */
    public int $tries = 3;

    /**
     * The model type.
     *
     */
    public static string $type;

    /**
     * Constructor.
     *
     */
    public function __construct(mixed $id, int $task = 0)
    {
        $this->id    = $id;
        $this->task  = $task;
        $this->queue = config('waterfall.queue_name');

        $this->task === 0 ? $this->handle(true) : null;
    }

    /**
     * Execute the job.
     *
     */
    public function handle(bool $skip = false) : void
    {
        $pipeline = $this->pipeline();

        $deleted = static::attempt(fn() => $this->remove($pipeline));

        $this->task += $deleted < config('waterfall.batch_size') ? 1 : 0;

        $this->task < count($pipeline) ? $this->proceed($skip) : null;
    }

    /**
     * Retrieve the complete set of tasks to perform.
     *
     */
    protected function pipeline() : array
    {
        return array_merge(
            [SoftDeleteTask::create(static::$type)],
            $this->tasks(),
            [HardDeleteTask::create(static::$type)],
        );
    }

    /**
     * Dispatch a follow-up job to continue the deletion process.
     *
     */
    protected function proceed(bool $skip) : void
    {
        if ($skip) {
            return;
        }

        sleep(config('waterfall.rest_time'));

        static::dispatch($this->id, $this->task);
    }

    /**
     * Execute the query for the current task in the given pipeline.
     *
     */
    protected function remove(array $pipeline) : int
    {
        $result = $pipeline[$this->task]->query($this->id);

        return $result instanceof Builder ? $result->delete() : $result;
    }

    /**
     * Assign the list of deletion tasks.
     *
     */
    abstract protected function tasks() : array;
}
