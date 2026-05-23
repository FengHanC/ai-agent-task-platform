<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * 获取任务消息列表（按时间倒序）
     */
    public function index(Task $task): JsonResponse
    {
        $messages = $task->messages()
            ->with('agent')
            ->latest('created_at')
            ->paginate(
                perPage: max(1, min((int) request('per_page', 50), 200)),
            );

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
     * 发送消息
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'type' => 'sometimes|string|in:system,agent,user,error',
        ]);

        $message = $task->addMessage(
            content: $validated['content'],
            type: $validated['type'] ?? 'user',
        );

        $message->load('agent');

        // 广播新消息
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => '消息发送成功',
            'data' => new MessageResource($message),
        ], 201);
    }
}
