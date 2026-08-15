<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Department;
use App\Models\Issue;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();

        $baseQuery = Issue::query()
            ->with(['department', 'area', 'sourceDailyReport.department', 'sourceDailyReport.reporter', 'creator', 'updater']);

        if ($search = $request->input('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $baseQuery->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('area_id')) {
            $baseQuery->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('from')) {
            $baseQuery->whereDate('first_reported_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $baseQuery->whereDate('first_reported_at', '<=', $request->input('to'));
        }

        // Metrics over the filtered set (before the status filter) so the
        // open/resolved/closed breakdown always sums to the total.
        $summary = [
            'total' => (clone $baseQuery)->count(),
            'open' => (clone $baseQuery)->where('status', 'open')->count(),
            'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
        ];

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        }

        $issues = $baseQuery->orderByDesc('first_reported_at')->get()->each(function (Issue $issue) {
            $issue->days_open = $issue->first_reported_at
                ? now()->startOfDay()->diffInDays($issue->first_reported_at->copy()->startOfDay())
                : null;
        });

        return view('issues.index', compact(
            'departments',
            'areas',
            'summary',
            'issues'
        ));
    }
}
