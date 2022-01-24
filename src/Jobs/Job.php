<?php declare(strict_types = 1);

namespace Waterfall\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
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
     * Execute the query and fetch records for the current task in the given pipeline.
     *
     */
    protected function fetch(array $pipeline) : Collection
    {
        $items = $pipeline[$this->task]
            ->generate($this->id)
            ->get();

        if ($pipeline[$this->task]->hydrate) {
            $items = $pipeline[$this->task]->model::hydrate($items->toArray());
        }

        return $items;
    }

    /**
     * Execute the job.
     *
     */
    public function handle(bool $skip = false) : void
    {
        $pipeline = $this->pipeline();

        $records = $this->hasHook($pipeline) ? static::attempt(fn() => $this->fetch($pipeline)) : null;

        // before
        if ($this->hasBeforeHook($pipeline)) {
            $pipeline[$this->task]['before']($records);
        }

        // delete
        $deleted = static::attempt(fn() => $this->remove($pipeline, $records));

        // after
        if ($this->hasAfterHook($pipeline)) {
            $pipeline[$this->task]['after']($records);
        }

        $batch = $pipeline[$this->task]->batch ?? config('waterfall.batch_size');

        $this->task += $deleted < $batch ? 1 : 0;

        $this->task < count($pipeline) ? $this->proceed($skip) : null;
    }

    /**
     * Determine if the current task has an after hook assigned to it.
     *
     */
    protected function hasAfterHook(array $pipeline) : bool
    {
        return filled($pipeline[$this->task]->after);
    }

    /**
     * Determine if the current task has a before hook assigned to it.
     *
     */
    protected function hasBeforeHook(array $pipeline) : bool
    {
        return filled($pipeline[$this->task]->before);
    }

    /**
     * Determine if the current task has a before or after hook assigned to it.
     *
     */
    protected function hasHook(array $pipeline) : bool
    {
        return $this->hasBeforeHook($pipeline) || $this->hasAfterHook($pipeline);
    }

    /**
     * Log the delay times between jobs for testing and debugging.
     *
     */
    private function log(Job $job) : static
    {
        if (! ($_ENV['waterfall_debug'] ?? false)) {
            return $job;
        }

        $_ENV['duration'] = ($_ENV['duration'] ?? 0) + $job->delay;

        return $job;
    }

    /**
     * Retrieve the complete set of tasks to perform.
     *
     */
    protected function pipeline() : array
    {
        return array_merge(
            [SoftDeleteTask::create()->model(static::$type)],
            $this->tasks(),
            [HardDeleteTask::create()->model(static::$type)],
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

        $job = (new static($this->id, $this->task))
            ->delay(config('waterfall.rest_time'));

        dispatch($this->log($job));
    }

    /**
     * Execute the query and remove records for the current task in the given pipeline.
     *
     */
    protected function remove(array $pipeline, Collection $records = null) : int
    {
        $result = $pipeline[$this->task]->generate($this->id);

        if (! $result instanceof Builder) {
            return $result;
        }

        return $result
            ->when(filled($records), fn($query) => $query->whereIn('id', $records->pluck('id')))
            ->delete();
    }

    /**
     * Assign the list of deletion tasks.
     *
     */
    abstract protected function tasks() : array;
}
