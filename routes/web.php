<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeeklyPlanController;
use App\Http\Controllers\WorkItemController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    // Shared Routes (Dashboard & Rankings)
    Route::middleware(['role:admin,manager,director'])->group(function () {
        Route::get('/', function () {
            return redirect()->route('dashboard.index');
        })->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
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
        Route::patch('/work-items/{item}/status', [WorkItemController::class, 'updateStatus'])->name('work-items.update-status');
        Route::post('/work-items/{item}/extend', [WorkItemController::class, 'extend'])->name('work-items.extend');
    });

    // Admin Only (Operational & User Management)
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');

        Route::get('/weekly-plan/create', [WeeklyPlanController::class, 'create'])->name('weekly-plans.create');
        Route::get('/weekly-plan/closing', [WeeklyPlanController::class, 'closing'])->name('weekly-plans.closing');
        Route::post('/api/weekly-plans', [WeeklyPlanController::class, 'store'])->name('api.weekly-plans.store');
        Route::patch('/api/weekly-plans/{plan}/status', [WeeklyPlanController::class, 'updateStatus'])->name('api.weekly-plans.update-status');

        Route::get('/daily-reports/navigate', [DailyReportController::class, 'navigate'])->name('daily-reports.navigate');
        Route::get('/daily-reports/create', [DailyReportController::class, 'create'])->name('daily-reports.create');
        Route::post('/daily-reports', [DailyReportController::class, 'store'])->name('daily-reports.store');
        Route::get('/daily-reports/{report}/edit', [DailyReportController::class, 'edit'])->name('daily-reports.edit');
        Route::put('/daily-reports/{report}', [DailyReportController::class, 'update'])->name('daily-reports.update');
        Route::get('/api/users/{user}/daily-report-options', [DailyReportController::class, 'getDailyReportOptions'])->name('api.users.daily-report-options');
    });

    // Daily Control Center (admin full, director read-only)
    Route::middleware(['role:admin,director'])->group(function () {
        Route::get('/daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');
    });
});
