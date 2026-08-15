<?php

namespace App\Services;

use App\Models\PlanScore;
use App\Models\WeeklyPlan;

class ScoreCalculatorService
{
    /**
     * Calculate the score for a weekly plan.
     */
    public function calculate(WeeklyPlan $plan): PlanScore
    {
        $baseScore = $this->getBaseScore($plan->status);
        $multiplier = $this->getMultiplier($plan->impact_level);
        $finalScore = $baseScore * $multiplier;

        return new PlanScore([
            'weekly_plan_id' => $plan->id,
            'base_score' => $baseScore,
            'multiplier' => $multiplier,
            'final_score' => $finalScore,
            'calculated_at' => now(),
        ]);
    }

    /**
     * Get base score according to status.
     */
    private function getBaseScore(string $status): int
    {
        return match ($status) {
            'completed' => 100,
            'completed_no_impact' => 60,
            'extended' => 40,
            'not_completed' => 0,
            default => 0,
        };
    }

    /**
     * Get multiplier according to impact level.
     */
    private function getMultiplier(string $impactLevel): float
    {
        return match ($impactLevel) {
            'low' => 1.0,
            'medium' => 1.2,
            'high' => 1.5,
            default => 1.0,
        };
    }
}
