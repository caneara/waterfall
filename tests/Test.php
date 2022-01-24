<?php declare(strict_types=1);

namespace Waterfall\Tests;

use Waterfall\ServiceProvider;
use Waterfall\Tests\Models\Post;
use Waterfall\Tests\Models\User;
use Orchestra\Testbench\TestCase;
use Waterfall\Tests\World\Builder;
use Waterfall\Tests\Jobs\DeleteUserJob;
use Waterfall\Tests\Jobs\DeleteUserBatchJob;
use Waterfall\Tests\Jobs\DeleteUserUsingKeyJob;
use Waterfall\Tests\Jobs\DeleteUserAfterHookJob;
use Waterfall\Tests\Jobs\DeleteUserBeforeHookJob;
use Waterfall\Tests\Jobs\DeleteUserBeforeAfterHookJob;
use Waterfall\Tests\Jobs\DeleteUserUsingRestrictionsJob;
use Waterfall\Tests\Jobs\DeleteUserUsingRestrictionsAndKeyJob;

class Test extends TestCase
{
    /**
     * Setup the test environment.
     *
     */
    protected function setUp() : void
    {
        parent::setUp();

        Builder::create();

        (new ServiceProvider(app()))->register();

        $this->loadMigrationsFrom(__DIR__ . '/Migrations');

        Builder::seed();
    }

    /**
     * Destroy the test environment.
     *
     */
    protected function tearDown() : void
    {
        Builder::destroy();
    }

    /** @test */
    public function it_soft_deletes_the_main_record_immediately() : void
    {
        $_ENV['waterfall_debug'] = false;
        $_ENV['duration']        = null;

        app('config')->set('queue.default', 'database');

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());

        DeleteUserJob::dispatch(1);

        $this->assertCount(1, User::get());
        $this->assertCount(8, Post::get());

        $this->assertNull($_ENV['duration']);
    }

    /** @test */
    public function it_can_delete_users() : void
    {
        $_ENV['waterfall_debug'] = false;
        $_ENV['duration']        = null;

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());

        DeleteUserJob::dispatch(1);

        $this->assertCount(1, User::get());
        $this->assertCount(4, Post::get());

        $this->assertEquals(2, User::first()->id);
        $this->assertEquals(5, Post::orderBy('id')->get()[0]->id);
        $this->assertEquals(6, Post::orderBy('id')->get()[1]->id);
        $this->assertEquals(7, Post::orderBy('id')->get()[2]->id);
        $this->assertEquals(8, Post::orderBy('id')->get()[3]->id);

        $this->assertNull($_ENV['duration']);
    }

    /** @test */
    public function it_can_delete_users_using_a_custom_key() : void
    {
        $_ENV['waterfall_debug'] = false;
        $_ENV['duration']        = null;

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());

        DeleteUserUsingKeyJob::dispatch(1);

        $this->assertCount(1, User::get());
        $this->assertCount(4, Post::get());

        $this->assertEquals(2, User::first()->id);
        $this->assertEquals(5, Post::orderBy('id')->get()[0]->id);
        $this->assertEquals(6, Post::orderBy('id')->get()[1]->id);
        $this->assertEquals(7, Post::orderBy('id')->get()[2]->id);
        $this->assertEquals(8, Post::orderBy('id')->get()[3]->id);

        $this->assertNull($_ENV['duration']);
    }

    /** @test */
    public function it_can_delete_users_using_restrictions() : void
    {
        $_ENV['waterfall_debug'] = false;
        $_ENV['duration']        = null;

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());

        DeleteUserUsingRestrictionsJob::dispatch(1);

        $this->assertCount(1, User::get());
        $this->assertCount(7, Post::get());

        $this->assertEquals(2, User::first()->id);
        $this->assertEquals(2, Post::orderBy('id')->get()[0]->id);
        $this->assertEquals(3, Post::orderBy('id')->get()[1]->id);
        $this->assertEquals(4, Post::orderBy('id')->get()[2]->id);
        $this->assertEquals(5, Post::orderBy('id')->get()[3]->id);
        $this->assertEquals(6, Post::orderBy('id')->get()[4]->id);
        $this->assertEquals(7, Post::orderBy('id')->get()[5]->id);
        $this->assertEquals(8, Post::orderBy('id')->get()[6]->id);

        $this->assertNull($_ENV['duration']);
    }

    /** @test */
    public function it_can_delete_users_using_restrictions_and_a_custom_key() : void
    {
        $_ENV['waterfall_debug'] = false;
        $_ENV['duration']        = null;

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());

        DeleteUserUsingRestrictionsAndKeyJob::dispatch(1);

        $this->assertCount(1, User::get());
        $this->assertCount(7, Post::get());

        $this->assertEquals(2, User::first()->id);
        $this->assertEquals(2, Post::orderBy('id')->get()[0]->id);
        $this->assertEquals(3, Post::orderBy('id')->get()[1]->id);
        $this->assertEquals(4, Post::orderBy('id')->get()[2]->id);
        $this->assertEquals(5, Post::orderBy('id')->get()[3]->id);
        $this->assertEquals(6, Post::orderBy('id')->get()[4]->id);
        $this->assertEquals(7, Post::orderBy('id')->get()[5]->id);
        $this->assertEquals(8, Post::orderBy('id')->get()[6]->id);

        $this->assertNull($_ENV['duration']);
    }

    /** @test */
    public function it_can_delete_users_in_batches() : void
    {
        $_ENV['waterfall_debug'] = true;
        $_ENV['duration']        = null;

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());

        DeleteUserBatchJob::dispatch(1);

        $this->assertCount(1, User::get());
        $this->assertCount(4, Post::get());

        $this->assertEquals(2, User::first()->id);
        $this->assertEquals(5, Post::orderBy('id')->get()[0]->id);
        $this->assertEquals(6, Post::orderBy('id')->get()[1]->id);
        $this->assertEquals(7, Post::orderBy('id')->get()[2]->id);
        $this->assertEquals(8, Post::orderBy('id')->get()[3]->id);

        $this->assertEquals(15, $_ENV['duration']);
    }

    /** @test */
    public function it_can_delete_users_and_calls_the_before_hook() : void
    {
        $_ENV['waterfall_debug'] = false;
        $_ENV['duration']        = null;
        $_ENV['before_items']    = null;
        $_ENV['after_items']     = null;

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());

        DeleteUserBeforeHookJob::dispatch(1);

        $this->assertCount(1, User::get());
        $this->assertCount(4, Post::get());

        $this->assertEquals(2, User::first()->id);
        $this->assertEquals(5, Post::orderBy('id')->get()[0]->id);
        $this->assertEquals(6, Post::orderBy('id')->get()[1]->id);
        $this->assertEquals(7, Post::orderBy('id')->get()[2]->id);
        $this->assertEquals(8, Post::orderBy('id')->get()[3]->id);

        $this->assertNull($_ENV['duration']);
        $this->assertNull($_ENV['after_items']);

        $this->assertEquals($_ENV['before_items']->pluck('id'), collect([1, 2, 3, 4]));
    }

    /** @test */
    public function it_can_delete_users_and_calls_the_after_hook() : void
    {
        $_ENV['waterfall_debug'] = false;
        $_ENV['duration']        = null;
        $_ENV['before_items']    = null;
        $_ENV['after_items']     = null;

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());

        DeleteUserAfterHookJob::dispatch(1);

        $this->assertCount(1, User::get());
        $this->assertCount(4, Post::get());

        $this->assertEquals(2, User::first()->id);
        $this->assertEquals(5, Post::orderBy('id')->get()[0]->id);
        $this->assertEquals(6, Post::orderBy('id')->get()[1]->id);
        $this->assertEquals(7, Post::orderBy('id')->get()[2]->id);
        $this->assertEquals(8, Post::orderBy('id')->get()[3]->id);

        $this->assertNull($_ENV['duration']);
        $this->assertNull($_ENV['before_items']);

        $this->assertEquals($_ENV['after_items'], collect([1, 2, 3, 4]));
    }

    /** @test */
    public function it_can_delete_users_and_calls_the_before_and_after_hook() : void
    {
        $_ENV['waterfall_debug'] = false;
        $_ENV['duration']        = null;
        $_ENV['before_items']    = null;
        $_ENV['after_items']     = null;

        $this->assertCount(2, User::get());
        $this->assertCount(8, Post::get());

        DeleteUserBeforeAfterHookJob::dispatch(1);

        $this->assertCount(1, User::get());
        $this->assertCount(4, Post::get());

        $this->assertEquals(2, User::first()->id);
        $this->assertEquals(5, Post::orderBy('id')->get()[0]->id);
        $this->assertEquals(6, Post::orderBy('id')->get()[1]->id);
        $this->assertEquals(7, Post::orderBy('id')->get()[2]->id);
        $this->assertEquals(8, Post::orderBy('id')->get()[3]->id);

        $this->assertNull($_ENV['duration']);

        $this->assertEquals($_ENV['before_items'], collect([1, 2, 3, 4]));
        $this->assertEquals($_ENV['after_items'], collect([1, 2, 3, 4]));
    }
}
