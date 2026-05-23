<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Agent;
use App\Models\Task;
use App\Models\User;

// ── 公开页面 ───────────────────────────────────────────────
Route::get('/', function () {
    // 如果已登录，直接跳转仪表盘
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return Inertia::render('Welcome');
});

// ── 认证路由（访客）───────────────────────────────────────
use App\Http\Controllers\Auth\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// ── 已登录页面 ─────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // 登出
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', function () {
        $stats = [
            'pending' => Task::where('status', 'pending')->count(),
            'processing' => Task::where('status', 'processing')->count(),
            'completed' => Task::where('status', 'completed')->count(),
            'failed' => Task::where('status', 'failed')->count(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'activities' => [], // 后续接入活动日志
        ]);
    })->name('dashboard');

    // Agent 页面路由
    Route::get('/agents', function () {
        $query = Agent::query();
        if (request('status') && request('status') !== 'all') {
            $query->where('status', request('status'));
        }
        return Inertia::render('Agents/Index', [
            'agents' => $query->latest()->paginate(15),
        ]);
    })->name('agents.index');

    Route::get('/agents/create', function () {
        $capabilityOptions = [
            ['value' => 'code_gen', 'label' => '代码生成'],
            ['value' => 'code_review', 'label' => '代码审查'],
            ['value' => 'analysis', 'label' => '分析'],
            ['value' => 'design', 'label' => '设计'],
            ['value' => 'testing', 'label' => '测试'],
            ['value' => 'documentation', 'label' => '文档'],
            ['value' => 'debugging', 'label' => '调试'],
            ['value' => 'data_processing', 'label' => '数据处理'],
        ];
        return Inertia::render('Agents/Create', [
            'capabilityOptions' => $capabilityOptions,
        ]);
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
});
