<?php

namespace App\Observers;

use App\Models\WeeklyPlan;
use App\Services\ScoreCalculatorService;
use Illuminate\Support\Facades\Auth;

class WeeklyPlanObserver
{
    protected $scoreService;

    public function __construct(ScoreCalculatorService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * Handle the WeeklyPlan "creating" event.
     */
    public function creating(WeeklyPlan $weeklyPlan): void
    {
        if (Auth::check()) {
            $weeklyPlan->created_by = Auth::id();
            $weeklyPlan->updated_by = Auth::id();
        }
    }

    /**
     * Handle the WeeklyPlan "updating" event.
     */
    public function updating(WeeklyPlan $weeklyPlan): void
    {
        if (Auth::check()) {
            $weeklyPlan->updated_by = Auth::id();
        }

        // Trigger score calculation only when status changes from 'planned' to another status
        if ($weeklyPlan->isDirty('status') && $weeklyPlan->getOriginal('status') === 'planned' && $weeklyPlan->status !== 'planned') {
            $score = $this->scoreService->calculate($weeklyPlan);

            // We use a saved callback or just save it directly here.
            // Since it's a one-to-one relationship and we are in the updating event,
            // we should be careful about database transactions if this were more complex.
        }
    }

    /**
     * Handle the WeeklyPlan "updated" event.
     */
    public function updated(WeeklyPlan $weeklyPlan): void
    {
        // Re-check scoring logic in 'updated' to ensure data exists if needed,
        // or just perform the save here to ensure the plan ID is definitely available.
        if ($weeklyPlan->wasChanged('status') && $weeklyPlan->getOriginal('status') === 'planned' && $weeklyPlan->status !== 'planned') {
            $scoreData = $this->scoreService->calculate($weeklyPlan);

            $weeklyPlan->score()->updateOrCreate(
                ['weekly_plan_id' => $weeklyPlan->id],
                $scoreData->toArray()
            );
        }
    }
}
