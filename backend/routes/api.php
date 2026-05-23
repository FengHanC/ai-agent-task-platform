<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::prefix('v1')->middleware('auth')->group(function () {
    // Agent CRUD
    Route::apiResource('agents', AgentController::class);
    Route::post('agents/{agent}/heartbeat', [AgentController::class, 'heartbeat'])->name('agents.heartbeat');
    Route::post('agents/{agent}/online', [AgentController::class, 'goOnline'])->name('agents.online');
    Route::post('agents/{agent}/offline', [AgentController::class, 'goOffline'])->name('agents.offline');

    // Task CRUD + 扩展端点
    Route::apiResource('tasks', TaskController::class);
    Route::post('tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
    Route::post('tasks/{task}/status', [TaskController::class, 'status'])->name('tasks.status');

    // 消息 API（嵌套在任务下）
    Route::get('tasks/{task}/messages', [MessageController::class, 'index'])->name('tasks.messages.index');
    Route::post('tasks/{task}/messages', [MessageController::class, 'store'])->name('tasks.messages.store');

    // 自动指派 Agent
    Route::post('tasks/{task}/auto-assign', [TaskController::class, 'autoAssign'])->name('tasks.auto-assign');
});
