<?php declare(strict_types = 1);

namespace Waterfall\Tests\World;

use Waterfall\ServiceProvider;
use Illuminate\Support\Facades\DB;

class Builder
{
    /**
     * Construct the world.
     *
     */
    public static function create() : void
    {
        $setup = [
            'driver'   => 'sqlite',
            'database' => __DIR__ . '/database.sqlite',
        ];

        @unlink(__DIR__ . '/database.sqlite');
        touch(__DIR__ . '/database.sqlite');

        app('config')->set('database.default', 'sqlite');
        app('config')->set('database.migrations', 'migrations');
        app('config')->set('database.connections.sqlite', $setup);

        app('config')->set('queue.default', 'sync');
        app('config')->set('queue.connections.sync', ['driver' => 'sync']);

        app('config')->set('waterfall.queue_name', 'deletions');
        app('config')->set('waterfall.queue_connection', '');
        app('config')->set('waterfall.batch_size', 1000);
        app('config')->set('waterfall.rest_time', 0);

        (new ServiceProvider(app()))->boot();
    }

    /**
     * Destroy the world.
     *
     */
    public static function destroy() : void
    {
        @unlink(__DIR__ . '/database.sqlite');
    }

    /**
     * Seed the database.
     *
     */
    public static function seed() : void
    {
        DB::table('users')->truncate();
        DB::table('posts')->truncate();

        DB::table('users')->insert(['id' => 1, 'name' => 'John Doe']);
        DB::table('users')->insert(['id' => 2, 'name' => 'Jane Doe']);

        DB::table('posts')->insert(['id' => 1, 'user_id' => 1, 'title' => 'Lorem ipsum']);
        DB::table('posts')->insert(['id' => 2, 'user_id' => 1, 'title' => 'Dolor sit']);
        DB::table('posts')->insert(['id' => 3, 'user_id' => 1, 'title' => 'Amet consectetur']);
        DB::table('posts')->insert(['id' => 4, 'user_id' => 1, 'title' => 'Adipiscing elit']);
        DB::table('posts')->insert(['id' => 5, 'user_id' => 2, 'title' => 'Sed do']);
        DB::table('posts')->insert(['id' => 6, 'user_id' => 2, 'title' => 'Eiusmod tempor']);
        DB::table('posts')->insert(['id' => 7, 'user_id' => 2, 'title' => 'Incididunt ut']);
        DB::table('posts')->insert(['id' => 8, 'user_id' => 2, 'title' => 'Labore et']);
    }
}
