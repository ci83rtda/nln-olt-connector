<?php

namespace App\Providers;

use App\Events\TaskCompleted;
use App\Events\TaskFetched;
use App\Jobs\BaseTaskJob;
use App\Listeners\TaskFetchedListener;
use App\Listeners\HandleTaskCompletion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            TaskCompleted::class,
            HandleTaskCompletion::class,
        );

        Event::listen(
            TaskFetched::class,
            TaskFetchedListener::class
        );
    }
}
