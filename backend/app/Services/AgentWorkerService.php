<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Message;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class AgentWorkerService
{
    private LlmClient $llm;

    public function __construct(LlmClient $llm)
    {
        $this->llm = $llm;
    }

    /**
     * 执行一次 Worker 循环：拉取待处理任务并调用 LLM 处理。
     * 返回本次处理的任务数量。
     */
    public function run(int $maxTasks = 3): int
    {
        $processed = 0;

        // 1. 找到可用的 Agent（online，有剩余容量）
        $availableAgents = Agent::where('status', 'online')
            ->whereColumn('current_tasks', '<', 'max_tasks')
            ->get();

        if ($availableAgents->isEmpty()) {
            Log::info('Agent Worker: 没有可用的 Agent');
            return 0;
        }

        foreach ($availableAgents as $agent) {
            if ($processed >= $maxTasks) {
                break;
            }

            // 2. 找到匹配 Agent 能力的待处理任务
            $task = $this->findPendingTaskForAgent($agent);
            if (!$task) {
                continue;
            }

            // 3. 执行一次任务处理
            $this->processTask($agent, $task);
            $processed++;
        }

        Log::info("Agent Worker: 本次处理了 {$processed} 个任务");
        return $processed;
    }

    /**
     * 处理用户消息的回复：已指派的 Agent 对用户消息做出回应。
     */
    public function processReply(Task $task, Message $userMessage): void
    {
        $agent = $task->agent;
        if (!$agent) {
            return;
        }

        $task->addMessage(
            "Agent 「{$agent->name}」正在回复...",
            'system',
            $agent->id
        );

        try {
            // 构建上下文：任务信息 + 最近对话历史
            $config = config('agent-worker.worker');
            $systemPrompt = str_replace(
                ['{name}', '{capabilities}'],
                [$agent->name, implode(', ', $agent->capabilities ?? [])],
                $config['system_prompt_template']
            );

            // 获取最近的对话上下文（最多 6 条消息作为上下文）
            $recentMessages = $task->messages()
                ->latest()
                ->take(6)
                ->get()
                ->reverse();

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            foreach ($recentMessages as $msg) {
                $role = match ($msg->type) {
                    'user' => 'user',
                    'agent' => 'assistant',
                    default => 'system',
                };
                $messages[] = [
                    'role' => $role,
                    'content' => $msg->content,
                ];
            }

            // 调用 LLM
            $result = $this->llm->chat($messages);

            // 写回结果
            $this->writeResultToMessages($task, $agent, $result);

            Log::info("Agent Worker: 任务 #{$task->id} Agent #{$agent->id} 回复完成");

        } catch (\Throwable $e) {
            Log::error("Agent Worker: 任务 #{$task->id} 回复失败", [
                'error' => $e->getMessage(),
                'agent_id' => $agent->id,
            ]);

            $task->addMessage(
                "Agent 回复失败：{$e->getMessage()}",
                'error',
                $agent->id
            );
        }
    }

    /**
     * 为 Agent 找一个匹配的待处理任务。
     * 匹配策略：任务类型在 Agent 的能力列表中。
     */
    private function findPendingTaskForAgent(Agent $agent): ?Task
    {
        $capabilities = $agent->capabilities ?? [];

        if (empty($capabilities)) {
            // Agent 没有能力标签，可以处理任何任务
            return Task::where('status', 'pending')
                ->whereNull('assigned_agent_id')
                ->oldest()
                ->first();
        }

        // 优先找类型在能力列表中的任务
        $task = Task::where('status', 'pending')
            ->whereNull('assigned_agent_id')
            ->whereIn('type', $capabilities)
            ->oldest()
            ->first();

        // 如果没有精确匹配，退而求其次找任意 pending 任务
        if (!$task) {
            $task = Task::where('status', 'pending')
                ->whereNull('assigned_agent_id')
                ->oldest()
                ->first();
        }

        return $task;
    }

    /**
     * 处理单个任务：指派 → 调 LLM → 写消息 → 完成/失败。
     */
    private function processTask(Agent $agent, Task $task): void
    {
        try {
            // 3a. 指派任务给 Agent
            $task->assignTo($agent);
            $task->addMessage(
                "Agent 「{$agent->name}」已接取任务，正在处理...",
                'system',
                $agent->id
            );

            // 3b. 构建提示词
            $config = config('agent-worker.worker');
            $systemPrompt = str_replace(
                ['{name}', '{capabilities}'],
                [$agent->name, implode(', ', $agent->capabilities ?? [])],
                $config['system_prompt_template']
            );
            $userPrompt = str_replace(
                ['{title}', '{description}', '{type}'],
                [$task->title, $task->description ?? '(无描述)', $task->type],
                $config['user_prompt_template']
            );

            // 3c. 调用 LLM
            $result = $this->llm->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ]);

            // 3d. 将 LLM 结果分块写入消息（避免一条超长消息）
            $this->writeResultToMessages($task, $agent, $result);

            // 3e. 标记完成
            $task->markCompleted();
            $task->addMessage(
                "Agent 「{$agent->name}」已完成任务处理。",
                'system',
                $agent->id
            );

            Log::info("Agent Worker: 任务 #{$task->id} 已由 Agent #{$agent->id} 完成");

        } catch (\Throwable $e) {
            Log::error("Agent Worker: 任务 #{$task->id} 处理失败", [
                'error' => $e->getMessage(),
                'agent_id' => $agent->id,
            ]);

            $task->addMessage(
                "Agent 「{$agent->name}」处理失败：{$e->getMessage()}",
                'error',
                $agent->id
            );
            $task->markFailed();
        }
    }

    /**
     * 将 LLM 结果分块写入消息（按段落分割，每条消息不超过 2000 字）。
     */
    private function writeResultToMessages(Task $task, Agent $agent, string $result): void
    {
        if (empty(trim($result))) {
            $task->addMessage('Agent 返回了空结果。', 'agent', $agent->id);
            return;
        }

        // 按双换行分段落
        $paragraphs = preg_split('/\n\s*\n/', trim($result));

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) {
                continue;
            }

            // 如果段落过长，按句子切分
            if (mb_strlen($paragraph) > 2000) {
                $sentences = preg_split('/(?<=[。！？.!?])\s*/', $paragraph);
                $chunk = '';
                foreach ($sentences as $sentence) {
                    $sentence = trim($sentence);
                    if (empty($sentence)) {
                        continue;
                    }
                    if (mb_strlen($chunk . $sentence) > 2000) {
                        if (!empty(trim($chunk))) {
                            $task->addMessage(trim($chunk), 'agent', $agent->id);
                        }
                        $chunk = $sentence;
                    } else {
                        $chunk .= $sentence;
                    }
                }
                if (!empty(trim($chunk))) {
                    $task->addMessage(trim($chunk), 'agent', $agent->id);
                }
            } else {
                $task->addMessage($paragraph, 'agent', $agent->id);
            }
        }
    }
}
