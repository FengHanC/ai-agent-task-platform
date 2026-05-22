<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Agent;
use App\Models\Task;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

// Agent 页面路由
Route::get('/agents', function () {
    return Inertia::render('Agents/Index', [
        'agents' => Agent::paginate(15),
    ]);
})->name('agents.index');

Route::get('/agents/create', function () {
    return Inertia::render('Agents/Create');
})->name('agents.create');

Route::get('/agents/{agent}', function (Agent $agent) {
    return Inertia::render('Agents/Show', [
        'agent' => $agent->load('tasks'),
    ]);
})->name('agents.show');

// 任务页面路由
Route::get('/tasks', function () {
    return Inertia::render('Tasks/Index', [
        'tasks' => Task::with('agent')->latest()->get(),
    ]);
})->name('tasks.index');

Route::get('/tasks/create', function () {
    return Inertia::render('Tasks/Create');
})->name('tasks.create');

Route::get('/tasks/{task}', function (Task $task) {
    $task->load('agent', 'messages.agent');
    return Inertia::render('Tasks/Show', [
        'task' => $task,
        'availableAgents' => Agent::where('status', 'online')
            ->whereColumn('current_tasks', '<', 'max_capacity')
            ->get(),
    ]);
})->name('tasks.show');
