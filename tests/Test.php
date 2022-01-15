<?php declare(strict_types=1);

namespace Waterfall\Tests;

use Waterfall\Tests\Models\Post;
use Waterfall\Tests\Models\User;
use Orchestra\Testbench\TestCase;
use Waterfall\Tests\World\Builder;
use Waterfall\Tests\Jobs\DeleteUserJob;
use Waterfall\Tests\Jobs\DeleteUserUsingKeyJob;
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
    public function it_can_delete_users() : void
    {
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

        $this->assertNull($_ENV['duration'] ?? null);
    }

    /** @test */
    public function it_can_delete_users_using_a_custom_key() : void
    {
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

        $this->assertNull($_ENV['duration'] ?? null);
    }

    /** @test */
    public function it_can_delete_users_using_restrictions() : void
    {
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

        $this->assertNull($_ENV['duration'] ?? null);
    }

    /** @test */
    public function it_can_delete_users_using_restrictions_and_a_custom_key() : void
    {
        $_ENV['waterfall_debug'] = false;

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

        $this->assertNull($_ENV['duration'] ?? null);
    }

    /** @test */
    public function it_can_delete_users_in_batches() : void
    {
        $_ENV['waterfall_debug'] = true;

        app('config')->set('waterfall.batch_size', 2);
        app('config')->set('waterfall.rest_time', 5);

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

        $this->assertEquals(15, $_ENV['duration']);
    }
}
