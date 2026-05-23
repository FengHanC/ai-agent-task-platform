<?php

namespace App\Http\Controllers\Api;

use App\Events\TaskStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Agent;
use App\Models\Task;
use App\Services\AgentAssignmentStrategy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * 任务列表（支持 status/type/priority/assigned_agent_id 筛选）
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with('agent');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('assigned_agent_id')) {
            $query->where('assigned_agent_id', $request->input('assigned_agent_id'));
        }

        $tasks = $query->latest()->paginate(
            perPage: max(1, min((int) $request->input('per_page', 15), 100)),
        );

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * 创建任务（status 默认 pending，type 默认 other，priority 默认 medium）
     *
     * @bodyParam auto_assign bool 是否自动指派 Agent（默认 false）
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();

        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'other',
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'pending',
            'assigned_agent_id' => $data['assigned_agent_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        // 如果指定了 auto_assign 且没有手动指派，自动匹配 Agent
        if ($request->boolean('auto_assign') && !$task->assigned_agent_id) {
            $strategy = app(AgentAssignmentStrategy::class);
            $agent = $strategy->assignBestAgent($task);

            if ($agent) {
                $task->load('agent');
                $message = "任务已自动指派给 Agent: {$agent->name}（能力匹配 + 负载均衡）";
                broadcast(new TaskStatusChanged($task, 'pending', 'processing', $message))->toOthers();
                $task->addMessage($message, 'system', $agent->id);
            }
        }

        $task->load('agent');

        return response()->json([
            'message' => '任务创建成功',
            'data' => new TaskResource($task),
        ], 201);
    }

    /**
     * 任务详情（含消息）
     */
    public function show(Task $task): JsonResponse
    {
        $task->load(['agent', 'messages' => function ($query) {
            $query->latest();
        }]);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * 更新任务
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task->update($request->validated());
        $task->load('agent');

        return response()->json([
            'message' => '任务更新成功',
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * 删除任务（软删除，进行中任务不可删除）
     */
    public function destroy(Task $task): JsonResponse
    {
        if ($task->status === 'processing') {
            return response()->json([
                'message' => '进行中的任务无法删除，请先取消或标记完成',
            ], 409);
        }

        $task->delete();

        return response()->json([
            'message' => '任务已删除',
        ]);
    }

    /**
     * 指派任务给 Agent
     *
     * @bodyParam agent_id int required Agent ID
     */
    public function assign(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
        ]);

        if (!in_array($task->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => '只能指派待处理或进行中的任务',
            ], 422);
        }

        $agent = Agent::findOrFail($request->input('agent_id'));

        if (!$agent->isAvailable()) {
            return response()->json([
                'message' => 'Agent 不可用（不在线或已达最大并发数）',
                'errors' => ['agent_id' => ['Agent 当前不可用']],
            ], 422);
        }

        $oldStatus = $task->status;

        // 如果之前指派给别的 Agent，先解除旧的
        if ($task->assigned_agent_id && $task->assigned_agent_id !== $agent->id && $task->status === 'processing') {
            $oldAgent = Agent::find($task->assigned_agent_id);
            if ($oldAgent && $oldAgent->current_tasks > 0) {
                $oldAgent->decrementTaskCount();
            }
        }

        $task->assignTo($agent);
        $task->load('agent');

        // 广播任务状态变更
        $message = "任务已指派给 Agent: {$agent->name}";
        broadcast(new TaskStatusChanged($task, $oldStatus, 'processing', $message))->toOthers();

        // 写入系统消息
        $task->addMessage($message, 'system', $agent->id);

        return response()->json([
            'message' => $message,
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * 自动指派 Agent（按能力匹配 + 负载均衡）
     */
    public function autoAssign(Request $request, Task $task): JsonResponse
    {
        $strategy = app(AgentAssignmentStrategy::class);
        $agent = $strategy->findBestAgent($task);

        if (!$agent) {
            return response()->json([
                'message' => '没有可用的 Agent 来指派此任务',
                'errors' => ['agent' => ['所有 Agent 均不可用（不在线或无空闲容量）']],
            ], 422);
        }

        $oldStatus = $task->status;

        $task->assignTo($agent);
        $task->load('agent');

        $message = "任务已自动指派给 Agent: {$agent->name}（能力匹配 + 负载均衡）";
        broadcast(new TaskStatusChanged($task, $oldStatus, 'processing', $message))->toOthers();
        $task->addMessage($message, 'system', $agent->id);

        return response()->json([
            'message' => $message,
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * 更新任务状态
     *
     * @bodyParam status string required 目标状态 (processing/completed/failed/cancelled)
     */
    public function status(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:processing,completed,failed,cancelled',
        ]);

        $newStatus = $request->input('status');
        $oldStatus = $task->status;

        switch ($newStatus) {
            case 'processing':
                if ($task->status !== 'pending') {
                    return response()->json([
                        'message' => '只有待处理的任务可以开始处理',
                    ], 422);
                }
                if (!$task->assigned_agent_id) {
                    return response()->json([
                        'message' => '请先指派 Agent 后再开始处理',
                    ], 422);
                }
                $agent = Agent::find($task->assigned_agent_id);
                if (!$agent) {
                    return response()->json([
                        'message' => '指派的 Agent 不存在',
                    ], 422);
                }
                $task->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);
                $agent->incrementTaskCount();

                $msg = "任务开始处理，指派给 Agent: {$agent->name}";
                $task->addMessage($msg, 'system', $agent->id);
                break;

            case 'completed':
                if ($task->status !== 'processing') {
                    return response()->json([
                        'message' => '只有进行中的任务可以标记完成',
                    ], 422);
                }
                $task->markCompleted();

                $agentName = $task->agent?->name ?? '未知';
                $msg = "任务已完成（{$agentName}）";
                $task->addMessage($msg, 'system', $task->agent?->id);
                break;

            case 'failed':
                if ($task->status !== 'processing') {
                    return response()->json([
                        'message' => '只有进行中的任务可以标记失败',
                    ], 422);
                }
                $task->markFailed();

                $agentName = $task->agent?->name ?? '未知';
                $msg = "任务处理失败（{$agentName}）";
                $task->addMessage($msg, 'system', $task->agent?->id);
                break;

            case 'cancelled':
                if (!in_array($task->status, ['pending', 'processing'])) {
                    return response()->json([
                        'message' => '只有待处理或进行中的任务可以取消',
                    ], 422);
                }
                $task->markCancelled();

                $msg = '任务已取消';
                $task->addMessage($msg, 'system');
                break;
        }

        // 广播任务状态变更
        broadcast(new TaskStatusChanged($task, $oldStatus, $newStatus))->toOthers();

        $task->load('agent');

        return response()->json([
            'message' => "任务状态已更新为: {$newStatus}",
            'data' => new TaskResource($task),
        ]);
    }
}
