<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * 获取任务列表（支持多条件筛选）
     */
    public function index(Request $request): JsonResponse
    {
        $validStatuses = ['pending', 'processing', 'completed', 'failed', 'cancelled'];
        $validTypes = ['code', 'analysis', 'design', 'review', 'other'];
        $validPriorities = ['low', 'medium', 'high', 'urgent'];

        $query = Task::with('agent');

        // 按状态筛选
        if ($request->filled('status')) {
            $status = $request->input('status');
            if (in_array($status, $validStatuses)) {
                $query->where('status', $status);
            } else {
                return response()->json([
                    'message' => '无效的状态值',
                    'errors' => ['status' => ['可选值: ' . implode(', ', $validStatuses)]],
                ], 422);
            }
        }

        // 按类型筛选
        if ($request->filled('type')) {
            $type = $request->input('type');
            if (in_array($type, $validTypes)) {
                $query->where('type', $type);
            }
        }

        // 按优先级筛选
        if ($request->filled('priority')) {
            $priority = $request->input('priority');
            if (in_array($priority, $validPriorities)) {
                $query->where('priority', $priority);
            }
        }

        // 按指派 Agent 筛选
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
     * 创建新任务
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();

        // 新任务默认状态为 pending
        $data['status'] = 'pending';

        // 如果指定了指派 Agent，检查是否可用
        if (!empty($data['assigned_agent_id'])) {
            $agent = Agent::find($data['assigned_agent_id']);
            if (!$agent || !$agent->isAvailable()) {
                return response()->json([
                    'message' => '指定的 Agent 不可用（不在线或已达最大并发数）',
                    'errors' => ['assigned_agent_id' => ['Agent 不可用']],
                ], 422);
            }
        }

        $task = Task::create($data);

        // 如果指定了指派 Agent，直接指派
        if (!empty($data['assigned_agent_id'])) {
            $task->assignTo($agent);
            $task->load('agent');
        }

        return response()->json([
            'message' => empty($data['assigned_agent_id'])
                ? '任务创建成功'
                : '任务创建成功并已指派给 Agent',
            'data' => new TaskResource($task),
        ], 201);
    }

    /**
     * 获取任务详情
     */
    public function show(Task $task): JsonResponse
    {
        $task->load('agent', 'creator');

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * 更新任务
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();

        // --- 处理指派 Agent 变更 ---
        if (array_key_exists('assigned_agent_id', $data)) {
            $newAgentId = $data['assigned_agent_id'];
            $oldAgentId = $task->assigned_agent_id;

            if ($newAgentId !== $oldAgentId) {
                // 解除旧 Agent 的任务计数
                if ($oldAgentId && in_array($task->status, ['processing', 'pending'])) {
                    $oldAgent = Agent::find($oldAgentId);
                    if ($oldAgent && $task->status === 'processing') {
                        $oldAgent->decrementTaskCount();
                    }
                }

                if (!empty($newAgentId)) {
                    // 检查新 Agent 是否可用
                    $newAgent = Agent::find($newAgentId);
                    if (!$newAgent || !$newAgent->isAvailable()) {
                        return response()->json([
                            'message' => '指定的 Agent 不可用（不在线或已达最大并发数）',
                            'errors' => ['assigned_agent_id' => ['Agent 不可用']],
                        ], 422);
                    }

                    // 如果任务当前未处理中，通过 assignTo 自动更新状态和计数
                    if ($task->status === 'pending' || $task->status === 'cancelled') {
                        $data['status'] = 'processing';
                        $data['started_at'] = now();
                        $newAgent->incrementTaskCount();
                    }
                } else {
                    // 移除了指派，回退到 pending
                    if ($task->status === 'processing') {
                        $data['status'] = 'pending';
                        $data['started_at'] = null;
                    }
                }
            }
        }

        // --- 处理手动状态变更 ---
        if (array_key_exists('status', $data)) {
            $newStatus = $data['status'];

            // 更新任务状态为 completed
            if ($newStatus === 'completed' && $task->status === 'processing') {
                $task->markCompleted();
                // markCompleted 已更新数据库，不需要再 update
                unset($data['status']);
            }
            // 更新任务状态为 failed
            elseif ($newStatus === 'failed' && $task->status === 'processing') {
                $task->markFailed();
                unset($data['status']);
            }
            // 取消任务
            elseif ($newStatus === 'cancelled' && in_array($task->status, ['pending', 'processing'])) {
                if ($task->status === 'processing' && $task->agent) {
                    $task->agent->decrementTaskCount();
                }
                $data['started_at'] = null;
            }
            // 重新打开已取消的任务
            elseif ($newStatus === 'pending' && $task->status === 'cancelled') {
                $data['started_at'] = null;
                $data['completed_at'] = null;
            }
        }

        // 执行更新（未通过专用方法处理的字段）
        if (!empty($data)) {
            $task->update($data);
        }

        $task->load('agent');

        return response()->json([
            'message' => '任务更新成功',
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * 删除任务（软删除）
     */
    public function destroy(Task $task): JsonResponse
    {
        if ($task->status === 'processing') {
            return response()->json([
                'message' => '进行中的任务无法删除，请先取消或标记完成',
            ], 409);
        }

        // 如果任务已指派，释放 Agent 计数
        if ($task->status === 'pending' && $task->assigned_agent_id) {
            // pending 但已指派，不占用计数，直接删除
        }

        $task->delete();

        return response()->json([
            'message' => '任务已删除',
        ]);
    }
}
