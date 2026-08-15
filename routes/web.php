<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\WeeklyPlanController;
use App\Http\Controllers\WorkItemController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    // Shared Routes (Dashboard & Rankings)
    Route::middleware(['role:admin,manager,director'])->group(function () {
        Route::get('/', [WeeklyPlanController::class, 'index'])->name('dashboard');
        Route::get('/rankings', [WeeklyPlanController::class, 'rankings'])->name('rankings');
        Route::get('/today', [WorkItemController::class, 'today'])->name('work-items.today');
        Route::get('/this-week', [WorkItemController::class, 'thisWeek'])->name('work-items.this-week');
        Route::get('/plan', [WorkItemController::class, 'plan'])->name('work-items.plan');
        Route::get('/progress', [WorkItemController::class, 'progress'])->name('work-items.progress');
        Route::get('/overdue', [WorkItemController::class, 'overdue'])->name('work-items.overdue');
        Route::get('/completed', [WorkItemController::class, 'completed'])->name('work-items.completed');
        Route::get('/calendar', [WorkItemController::class, 'calendar'])->name('work-items.calendar');
        Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');
        Route::get('/person', [WorkItemController::class, 'person'])->name('work-items.person');
        Route::get('/area', [WorkItemController::class, 'area'])->name('work-items.area');
    });

    // Admin Only (Operational)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/weekly-plan/create', [WeeklyPlanController::class, 'create'])->name('weekly-plans.create');
        Route::get('/weekly-plan/closing', [WeeklyPlanController::class, 'closing'])->name('weekly-plans.closing');
        Route::post('/api/weekly-plans', [WeeklyPlanController::class, 'store'])->name('api.weekly-plans.store');
        Route::patch('/api/weekly-plans/{plan}/status', [WeeklyPlanController::class, 'updateStatus'])->name('api.weekly-plans.update-status');

        Route::get('/daily-reports/create', [DailyReportController::class, 'create'])->name('daily-reports.create');
        Route::post('/daily-reports', [DailyReportController::class, 'store'])->name('daily-reports.store');
        Route::get('/daily-reports/{report}/edit', [DailyReportController::class, 'edit'])->name('daily-reports.edit');
        Route::put('/daily-reports/{report}', [DailyReportController::class, 'update'])->name('daily-reports.update');
    });

    // Daily Control Center (admin full, director read-only)
    Route::middleware(['role:admin,director'])->group(function () {
        Route::get('/daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');
    });
});
