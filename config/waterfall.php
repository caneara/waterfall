<?php declare(strict_types = 1);

return [

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | This value controls the name of the queue that the jobs should be pushed
    | to. It is advisable to push the jobs to a separate queue that has a small
    | number of workers. Too many workers all running jobs increases strain on
    | the database, thereby negating the value this package provides.
    |
    */
    'queue_name' => 'deletions',

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    |
    | This value controls the maximum number of records that Waterfall will
    | attempt to delete per query. In most cases, the database should be fine
    | with the default figure, however if the database is under strain, then
    | you might want to consider lowering it.
    |
    */
    'batch_size' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Rest Time
    |--------------------------------------------------------------------------
    |
    | This value controls the number of seconds that Waterfall will wait when
    | dispatching a follow-up job to continue the deletion process. The pause
    | is added to the dispatched job using the standard 'delay' method.
    |
    */
    'rest_time' => 5,

];
