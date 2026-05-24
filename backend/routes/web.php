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
use App\Http\Controllers\Auth\PasswordResetController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // 密码重置
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('password.email');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.reset');
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
            'online_agents' => Agent::where('status', 'online')->count(),
            'total_agents' => Agent::count(),
            'today_completed' => Task::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
        ];

        // 最近消息活动
        $activities = \App\Models\Message::with('task.agent')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'type' => $msg->type,
                'content' => $msg->content,
                'task_title' => $msg->task?->title,
                'task_id' => $msg->task_id,
                'created_at' => $msg->created_at?->diffForHumans(),
            ]);

        // 最近待处理任务（按优先级排序）
        $pendingTasks = Task::with('agent')
            ->where('status', 'pending')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END")
            ->latest()
            ->take(5)
            ->get();

        // 按任务状态提取最近动态
        $recentActivities = Task::with('agent')
            ->whereIn('status', ['processing', 'completed', 'failed'])
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(function ($task) {
                $statusMap = [
                    'processing' => '开始处理',
                    'completed' => '已完成',
                    'failed' => '处理失败',
                ];
                return [
                    'id' => $task->id,
                    'type' => "task_{$task->status}",
                    'description' => "【{$task->title}】{$statusMap[$task->status]}"
                        . ($task->agent ? " - {$task->agent->name}" : ''),
                    'task_title' => $task->title,
                    'task_id' => $task->id,
                    'created_at' => $task->updated_at?->toISOString(),
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'activities' => $activities,
            'pendingTasks' => $pendingTasks,
            'recentActivities' => $recentActivities,
            'agentsOverview' => Agent::select('id', 'name', 'status', 'current_tasks', 'max_capacity', 'capabilities')
                ->latest()
                ->take(10)
                ->get(),
        ]);
    })->name('dashboard');

    // Agent 页面路由
    Route::get('/agents', function () {
        $query = Agent::query();
        if (request('status') && request('status') !== 'all') {
            $query->where('status', request('status'));
        }
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        return Inertia::render('Agents/Index', [
            'agents' => $query->latest()->paginate(15),
            'filters' => request()->only(['status', 'search']),
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

    Route::get('/agents/{agent}/edit', function (Agent $agent) {
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
        return Inertia::render('Agents/Edit', [
            'agent' => $agent,
            'capabilityOptions' => $capabilityOptions,
        ]);
    })->name('agents.edit');

    // 任务页面路由
    Route::get('/tasks', function () {
        $tasks = Task::with('agent')
            ->latest()
            ->paginate(min((int) request('per_page', 50), 200));

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
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

    Route::get('/tasks/{task}/edit', function (Task $task) {
        $task->load('agent');
        return Inertia::render('Tasks/Edit', [
            'task' => $task,
        ]);
    })->name('tasks.edit');
});
