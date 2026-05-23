<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\Task;
use App\Services\AgentWorkerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * 获取任务的消息列表
     */
    public function index(Task $task): JsonResponse
    {
        $messages = $task->messages()
            ->with('agent')
            ->latest()
            ->paginate(min((int) request('per_page', 50), 200));

        return response()->json([
            'data' => MessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * 发送消息到任务
     *
     * @bodyParam content string required 消息内容
     * @bodyParam type string 消息类型 (system/agent/user/error)，默认 user
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:10000',
            'type' => 'nullable|string|in:system,agent,user,error',
        ]);

        $message = Message::create([
            'task_id' => $task->id,
            'agent_id' => null,
            'type' => $request->input('type', 'user'),
            'content' => $request->input('content'),
            'created_at' => now(),
        ]);

        $message->load('agent');

        // 广播新消息
        broadcast(new MessageSent($message))->toOthers();

        // 用户消息 → 触发 Agent 回复
        if ($message->type === 'user' && $task->status === 'processing' && $task->agent) {
            try {
                $worker = app(AgentWorkerService::class);
                $worker->processReply($task, $message);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Agent 回复失败', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => '消息发送成功',
            'data' => new MessageResource($message),
        ], 201);
    }
}
