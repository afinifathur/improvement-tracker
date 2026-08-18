<?php

namespace App\Providers;

use App\Models\WeeklyPlan;
use App\Observers\WeeklyPlanObserver;
use Illuminate\Support\Carbon;
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
        Carbon::setLocale('id');

        WeeklyPlan::observe(WeeklyPlanObserver::class);
    }
}
