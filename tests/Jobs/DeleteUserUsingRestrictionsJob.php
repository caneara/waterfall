<?php declare(strict_types = 1);

namespace Waterfall\Tests\Jobs;

use Waterfall\Jobs\Job;
use Waterfall\Tasks\Task;
use Waterfall\Tests\Models\Post;
use Waterfall\Tests\Models\User;

class DeleteUserUsingRestrictionsJob extends Job
{
    /**
     * The model type.
     *
     */
    public static string $type = User::class;

    /**
     * Assign the list of deletion tasks.
     *
     */
    protected function tasks() : array
    {
        return [
            Task::create()
                ->model(Post::class)
                ->query(function($query) {
                    return $query->where('title', 'Lorem ipsum');
                }),
        ];
    }
}
