<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Agent;
use App\Models\Task;
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
            perPage: min((int) $request->input('per_page', 15), 100),
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

        // 如果指定了 Agent 且可用，自动指派
        if (!empty($data['assigned_agent_id'])) {
            $agent = Agent::find($data['assigned_agent_id']);
            if ($agent && $agent->isAvailable()) {
                $task->assignTo($agent);
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

        // 如果之前指派给别的 Agent，先解除旧的
        if ($task->assigned_agent_id && $task->assigned_agent_id !== $agent->id && $task->status === 'processing') {
            $oldAgent = Agent::find($task->assigned_agent_id);
            if ($oldAgent) {
                $oldAgent->decrementTaskCount();
            }
        }

        $task->assignTo($agent);
        $task->load('agent');

        return response()->json([
            'message' => "任务已指派给 Agent: {$agent->name}",
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
                $task->assignTo($agent);
                break;

            case 'completed':
                if ($task->status !== 'processing') {
                    return response()->json([
                        'message' => '只有进行中的任务可以标记完成',
                    ], 422);
                }
                $task->markCompleted();
                break;

            case 'failed':
                if ($task->status !== 'processing') {
                    return response()->json([
                        'message' => '只有进行中的任务可以标记失败',
                    ], 422);
                }
                $task->markFailed();
                break;

            case 'cancelled':
                if (!in_array($task->status, ['pending', 'processing'])) {
                    return response()->json([
                        'message' => '只有待处理或进行中的任务可以取消',
                    ], 422);
                }
                // 如果进行中被取消，释放 Agent 计数
                if ($task->status === 'processing' && $task->agent) {
                    $task->agent->decrementTaskCount();
                }
                $task->update(['status' => 'cancelled', 'completed_at' => now()]);
                break;
        }

        $task->load('agent');

        return response()->json([
            'message' => "任务状态已更新为: {$newStatus}",
            'data' => new TaskResource($task),
        ]);
    }
}
