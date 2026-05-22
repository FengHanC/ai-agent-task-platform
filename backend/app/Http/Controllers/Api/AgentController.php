<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * 获取 Agent 列表（支持按状态筛选）
     */
    public function index(Request $request): JsonResponse
    {
        $validStatuses = ['online', 'offline', 'busy'];

        $query = Agent::query();

        if ($request->filled('status')) {
            $status = $request->input('status');

            if (in_array($status, $validStatuses)) {
                $query->where('status', $status);
            } else {
                return response()->json([
                    'message' => '无效的状态值，可选值: online, offline, busy',
                    'errors' => ['status' => ["状态值无效，可选值: online, offline, busy"]],
                ], 422);
            }
        }

        $agents = $query->latest()->paginate(
            perPage: min((int) $request->input('per_page', 15), 100),
        );

        return response()->json([
            'data' => AgentResource::collection($agents),
            'meta' => [
                'current_page' => $agents->currentPage(),
                'last_page' => $agents->lastPage(),
                'per_page' => $agents->perPage(),
                'total' => $agents->total(),
            ],
        ]);
    }

    /**
     * 创建新的 Agent
     */
    public function store(StoreAgentRequest $request): JsonResponse
    {
        $agent = Agent::create(array_merge(
            $request->validated(),
            ['status' => 'offline'],
        ));

        return response()->json([
            'message' => 'Agent 创建成功',
            'data' => new AgentResource($agent),
        ], 201);
    }

    /**
     * 获取单个 Agent 详情
     */
    public function show(Agent $agent): JsonResponse
    {
        $agent->loadCount('tasks');

        return response()->json([
            'data' => new AgentResource($agent),
        ]);
    }

    /**
     * 更新 Agent 信息
     */
    public function update(UpdateAgentRequest $request, Agent $agent): JsonResponse
    {
        $agent->update($request->validated());

        return response()->json([
            'message' => 'Agent 更新成功',
            'data' => new AgentResource($agent->fresh()),
        ]);
    }

    /**
     * 删除 Agent（软删除）
     */
    public function destroy(Agent $agent): JsonResponse
    {
        // 如果有进行中的任务，阻止删除
        if ($agent->tasks()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json([
                'message' => '该 Agent 有待处理或进行中的任务，无法删除',
            ], 409);
        }

        $agent->delete();

        return response()->json([
            'message' => 'Agent 已删除',
        ]);
    }
}
