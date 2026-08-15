<?php

namespace App\Providers;

use App\Models\WeeklyPlan;
use App\Observers\WeeklyPlanObserver;
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

    public function boot(): void
    {
        WeeklyPlan::observe(WeeklyPlanObserver::class);
    }
}
