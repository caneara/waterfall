<!-- Screenshot -->
<p align="center">
    <img src="resources/wallpaper.jpg" alt="Wallpaper">
</p>

<!-- Badges -->
<p align="center">
  <img src="resources/version.svg" alt="Version">
  <img src="resources/license.svg" alt="License">
</p>

# Waterfall

This package enables a Laravel application to perform database cascading delete operations in staggered batches. The primary benefit of this approach, is that it avoids overwhelming the database with a massive amount of record deletion tasks when operating large-scale applications e.g. analytic platforms.

## Who is this for?

If you're building a small application, or your database is unlikely to ever see cascade deletions exceeding
a few thousand records, then you can probably make do without this package. Simply enforce cascade deleting
in your migrations as you normally would.

If your database contains or will contain hundreds of thousands, millions or even billions of records, and the deletion of a record will cause a cascade involving a similar number of records, then it is likely to overwhelm your database. In this scenario, Waterfall can be a good choice.

The package is also a good choice if you're using a so-called 'NewSQL' platform that doesn't offer cascade deletion (e.g. because of a lack of foreign key constraints).

## How does it work?

The process is fairly simple. You define a job e.g. `DeleteUserJob` that extends Waterfall's own `Waterfall\Jobs\Job`. Within this job, you configure the cascade tasks that need to be performed. When you're ready to delete a 'user' record, you dispatch `DeleteUserJob` and provide it with the ID of the 'user'.

Waterfall will then perform the following tasks

1. Soft delete the main record (user).
2. Iterate through the cascade tasks.
    1. Delete a batch of related records (1000).
    2. If more records are available, sleep for a short time, then dispatch another job to repeat (i).
    3. If no more records are available, sleep for a short time, then dispatch another job to continue 2.
3. When all tasks have been completed, hard delete the main record (user).

## Installation

Pull in the package using Composer:

```bash
composer require mattkingshott/waterfall
```

## Configuration

Waterfall includes a configuration file that allows you to:

1. Set the queue name to use for the cascade deletion jobs (defaults to 'deletion').
2. Set the batch size / number of records to delete per query (defaults to 1000).
3. Set the rest time in seconds to give the database between batches (defaults to 5).

> Make sure to only create a small number of workers for the queue e.g. 2 or 3. Too many workers risks overwhelming the database, which completely negates the purpose of the package.

If you wish to change any of these values, publish the configuration file using Artisan:

```bash
php artisan vendor:publish
```

## Usage

In order for Waterfall to delete records without triggering a cascade, the associated `Model` class must implement Laravel's built-in soft deleting. Begin by adding the `SoftDeletes` trait to the model class e.g.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;
}
```

Next, we need to create a job that extends `Waterfall\Jobs\Job` e.g.

```php
<?php

namespace App\Jobs;

use App\Models\User;
use Waterfall\Jobs\Job;

class DeleteUserJob extends Job
{
    public static string $type = User::class;
}
```

Note the inclusion of a `$type` attribute on the job. This should be set to the model type of the main record that is being deleted. In this example, we're deleting a user, so the `$type` is `User::class`.

### Configuring the tasks

At the moment, all the job will do, is soft-delete the user, then hard-delete it. Without intermediate tasks, this will still trigger a cascading delete. To prevent this, let's add some tasks. We do this by adding a `tasks()` method to the job, and then creating one or more `Waterfall\Tasks\Task` e.g.

```php
use Waterfall\Tasks\Task;

protected function tasks() : array
{
    return [
        Task::create(Post::class),
    ];
}
```

Waterfall will intepret this as

```sql
DELETE FROM `posts` WHERE `user_id` = ? LIMIT 1000
```

#### Configuring the foreign key

Notice how Waterfall has guessed the foreign key by using the class we defined for `$type`. In many cases, this will be correct. However, if you need to use a different key, then you can set it explicitly:

```php
protected function tasks() : array
{
    return [
        Task::create(Post::class, 'author_id'),
    ];
}
```

#### Modifying the underlying query

In many cases, you'll simply want to delete all records associated with the main record. However, if you need to be more specific e.g. you want to include a `WHERE` condition, or add a `JOIN`, then you can modify the query.

To do this, supply a `Closure` that accepts the current `$query` as a parameter. You are then free to modify the query however you wish e.g.

```php
protected function tasks() : array
{
    return [
        Task::create(Post::class, function($query) {
            return $query->where('year', 2022);
        }),
    ];
}
```

Waterfall will intepret this as

```sql
DELETE FROM `posts` WHERE `user_id` = ? AND `year` = 2022 LIMIT 1000
```

If you need to also specify a different foreign key, then supply the `Closure` as the third parameter e.g.

```php
protected function tasks() : array
{
    return [
        Task::create(Post::class, 'author_id' function($query) {
            return $query->where('year', 2022);
        }),
    ];
}
```

Waterfall will intepret this as

```sql
DELETE FROM `posts` WHERE `author_id` = ? AND `year` = 2022 LIMIT 1000
```

### How to order your tasks

In order to prevent cascading delete operations from taking place, you have to perform your delete tasks in reverse. To better illustrate this, consider the following example database:

```
users -> posts -> likes
```

If you were to delete a 'user', it would trigger a cascade to delete all of the user's 'posts', and then all of the 'likes' accumulated for the user's 'posts'. Likewise, if you were to just delete a 'post', it would not delete the 'user', but it would cause a cascade to delete all the 'likes' accumulated for the 'post'. We therefore have to perform the deletion tasks in the following order:

```
likes -> posts -> users
```

Here's a complete job example that covers how to do this. Note that this assumes that 'posts' has a `user_id` foreign key, and that 'likes' has `post_id` foreign key.

```php
<?php

namespace App\Jobs;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Waterfall\Jobs\Job;
use Waterfall\Tasks\Task;

class DeleteUserJob extends Job
{
    public static string $type = User::class;

    protected function tasks() : array
    {
        return [
            Task::create(Like::class, 'posts.user_id' function($query) {
                return $query->join('posts', 'likes.post_id', '=', 'posts.id');
            }),
            Task::create(Post::class, 'user_id'),
        ];
    }
}
```

Waterfall will intepret this as

```sql
DELETE FROM `likes` INNER JOIN `posts` ON `likes`.`post_id` = `posts`.`id` WHERE `posts`.`user_id` = ? LIMIT 1000
DELETE FROM `posts` WHERE `user_id` = ? LIMIT 1000
```

## Enabling cascade deletions at a database level

There are different schools of thought on whether you should continue to enable cascading deletes within your database migrations.

In theory, if you are using this package and your jobs are correctly configured, it should not be necessary to enable cascading deletes. However, if the job misses something, and you have disabled cascading deletes, then your database will throw an error (potentially leaving you with corrupted data).

Another approach, is to disable cascading deletes in development, and then enable them when deploying to production. Using this strategy, you will hopefully discover any issues with the task lists of your jobs before your code gets into production. However, if something does get through, the database will at least ensure that the data is not corrupted (though potentially at the risk of a crippling deletion).

> It should also be noted that if you do disable cascading deletes, then deleting records outside of your application becomes cumbersome e.g. within a database tool like MySQL Workbench.

## A word on transactions

Since Waterfall performs deletions in batches and with pauses to give the database time to execute its tasks, it is not possible to use transactions. As best as I can see, there is no way round this as any long-running task using a transaction would probably lock up the database, thereby negating the entire performance benefit brought by Waterfall.

If someone is able to come up with an efficient way to make transactions work in this configuration, I'd be more than happy to entertain a PR.

## Contributing

Thank you for considering a contribution to Waterfall. You are welcome to submit a PR containing improvements, however if they are substantial in nature, please also be sure to include a test or tests.

## Support the project

If you'd like to support the development of Waterfall, then please consider [sponsoring me](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YBEHLHPF3GUVY&source=url). Thanks so much!

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.