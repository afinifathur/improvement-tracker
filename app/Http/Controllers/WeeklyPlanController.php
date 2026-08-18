<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWeeklyPlanRequest;
use App\Http\Requests\UpdateWeeklyStatusRequest;
use App\Models\User;
use App\Models\WeeklyPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WeeklyPlanController extends Controller
{
    public function index()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $plans = WeeklyPlan::whereBetween('week_start_date', [$startOfWeek, $endOfWeek])->get();

        $totalPlans = $plans->count();
        $completedPlans = $plans->where('status', 'completed')->count();
        $completionRate = $totalPlans > 0 ? round(($completedPlans / $totalPlans) * 100) : 0;
        $avgScore = WeeklyPlan::join('plan_scores', 'weekly_plans.id', '=', 'plan_scores.weekly_plan_id')
            ->whereBetween('week_start_date', [$startOfWeek, $endOfWeek])
            ->avg('final_score') ?? 0;

        $performanceData = User::operationalPersonnel()
            ->withCount(['weeklyPlans as total' => function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('week_start_date', [$startOfWeek, $endOfWeek]);
            }])
            ->withCount(['weeklyPlans as completed' => function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('week_start_date', [$startOfWeek, $endOfWeek])->where('status', 'completed');
            }])
            ->get()
            ->map(function ($user) use ($startOfWeek, $endOfWeek) {
                $user->rate = $user->total > 0 ? round(($user->completed / $user->total) * 100) : 0;
                $user->avg_score = WeeklyPlan::join('plan_scores', 'weekly_plans.id', '=', 'plan_scores.weekly_plan_id')
                    ->where('user_id', $user->id)
                    ->whereBetween('week_start_date', [$startOfWeek, $endOfWeek])
                    ->avg('final_score') ?? 0;

                return (object) [
                    'user' => $user,
                    'total' => $user->total,
                    'completed' => $user->completed,
                    'rate' => $user->rate,
                    'avg_score' => $user->avg_score,
                ];
            });

        $topPerformer = $performanceData->sortByDesc('avg_score')->first()->user ?? null;
        if ($topPerformer) {
            $topPerformer->avg_score = $performanceData->where('user.id', $topPerformer->id)->first()->avg_score;
        }

        return view('dashboard', compact('totalPlans', 'completedPlans', 'completionRate', 'avgScore', 'performanceData', 'topPerformer'));
    }

    public function create()
    {
        $personnel = User::operationalPersonnel()->orderBy('name')->get();

        return view('weekly-plans.create', compact('personnel'));
    }

    public function closing()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $plans = WeeklyPlan::with(['user', 'workItems'])
            ->whereBetween('week_start_date', [$startOfWeek, $endOfWeek])
            ->orderBy('status', 'asc')
            ->get();

        $stats = (object) [
            'total' => $plans->count(),
            'completed' => $plans->where('status', 'completed')->count(),
            'pending' => $plans->where('status', 'planned')->count(),
            'extended' => $plans->where('status', 'extended')->count(),
        ];

        return view('weekly-plans.closing', compact('plans', 'stats'));
    }

    public function rankings()
    {
        $rankings = User::operationalPersonnel()
            ->get()
            ->map(function ($user) {
                $plans = WeeklyPlan::where('user_id', $user->id)->get();
                $totalPlans = $plans->count();
                $completedPlans = $plans->where('status', 'completed')->count();
                $totalScore = WeeklyPlan::join('plan_scores', 'weekly_plans.id', '=', 'plan_scores.weekly_plan_id')
                    ->where('user_id', $user->id)
                    ->sum('final_score');

                return (object) [
                    'user' => $user,
                    'total_score' => $totalScore,
                    'total_plans' => $totalPlans,
                    'rate' => $totalPlans > 0 ? round(($completedPlans / $totalPlans) * 100) : 0,
                ];
            })->sortByDesc('total_score')->values();

        $avgEfficiency = $rankings->avg('rate') ?? 0;
        $totalLogs = WeeklyPlan::count();

        $categories = WeeklyPlan::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category')
            ->map(function ($total) use ($totalLogs) {
                return $totalLogs > 0 ? round(($total / $totalLogs) * 100) : 0;
            });

        return view('rankings.index', compact('rankings', 'avgEfficiency', 'totalLogs', 'categories'));
    }

    /**
     * Store a newly created weekly plan in storage.
     */
    public function store(StoreWeeklyPlanRequest $request): JsonResponse|RedirectResponse
    {
        $plan = WeeklyPlan::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Weekly plan created successfully.',
                'data' => $plan->load('user'),
            ], 201);
        }

        return redirect()->route('weekly-plans.closing')
            ->with('status', 'Rencana mingguan berhasil dibuat.');
    }

    /**
     * Update the status of a weekly plan.
     */
    public function updateStatus(UpdateWeeklyStatusRequest $request, WeeklyPlan $plan): JsonResponse
    {
        return DB::transaction(function () use ($request, $plan) {
            // Update basic fields
            $plan->status = $request->status;
            $plan->notes = $request->notes;

            if ($request->has('category_corrected')) {
                $plan->category_corrected = $request->category_corrected;
            }

            if ($request->filled('week_end_date')) {
                $plan->week_end_date = $request->week_end_date;
            }

            $plan->save();

            // Handle proofs if provided
            if ($request->hasFile('proofs')) {
                foreach ($request->file('proofs') as $file) {
                    $path = $file->store('proofs', 'public');

                    $plan->proofs()->create([
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                    ]);
                }
            }

            return response()->json([
                'message' => 'Weekly plan status updated and score calculated.',
                'data' => $plan->load(['proofs', 'score']),
            ]);
        });
    }
}
