<?php

use App\Http\Controllers\WeeklyPlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/weekly-plans', [WeeklyPlanController::class, 'store']);
    Route::patch('/weekly-plans/{plan}/status', [WeeklyPlanController::class, 'updateStatus']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
