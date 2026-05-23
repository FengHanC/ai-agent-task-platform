<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::prefix('v1')->group(function () {
    // Agent CRUD
    Route::apiResource('agents', AgentController::class);

    // Task CRUD + 扩展端点
    Route::apiResource('tasks', TaskController::class);
    Route::post('tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
    Route::post('tasks/{task}/status', [TaskController::class, 'status'])->name('tasks.status');

    // 消息 API（嵌套在任务下）
    Route::get('tasks/{task}/messages', [MessageController::class, 'index'])->name('tasks.messages.index');
    Route::post('tasks/{task}/messages', [MessageController::class, 'store'])->name('tasks.messages.store');
});
