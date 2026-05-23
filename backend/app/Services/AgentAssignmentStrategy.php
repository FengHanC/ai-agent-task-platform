<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Task;

class AgentAssignmentStrategy
{
    /**
     * 任务类型 → 所需能力的映射
     */
    private const TYPE_CAPABILITY_MAP = [
        'code'     => ['code_gen', 'debugging'],
        'analysis' => ['analysis'],
        'design'   => ['design'],
        'review'   => ['code_review'],
        'other'    => [], // 无特定要求，匹配任意能力
    ];

    /**
     * 为指定任务自动匹配合适的 Agent
     *
     * @return Agent|null 找到的最佳 Agent，无可用 Agent 时返回 null
     */
    public function findBestAgent(Task $task): ?Agent
    {
        // 1. 获取所有在线且有容量的 Agent
        $candidates = Agent::where('status', 'online')
            ->whereColumn('current_tasks', '<', 'max_capacity')
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // 2. 根据能力匹配 + 负载评分
        $requiredCaps = $this->getRequiredCapabilities($task->type);

        $scored = $candidates->map(function (Agent $agent) use ($requiredCaps) {
            return [
                'agent'            => $agent,
                'capability_score' => $this->calculateCapabilityScore($agent, $requiredCaps),
                'load_score'       => $this->calculateLoadScore($agent),
            ];
        });

        // 综合得分 = 能力分 × 权重 + 负载分 × 权重
        // 能力至关重要：有能力匹配的远优于无匹配的
        // 负载是次要的：同等能力下选最空闲的
        $best = $scored->sortByDesc(function (array $item) {
            // 能力分权重 100，负载分权重 1
            return ($item['capability_score'] * 100) + $item['load_score'];
        })->first();

        return $best ? $best['agent'] : null;
    }

    /**
     * 为指定任务类型自动指派 Agent，并立即开始处理
     *
     * @return Agent|null 被指派的 Agent，失败时返回 null
     */
    public function assignBestAgent(Task $task): ?Agent
    {
        $agent = $this->findBestAgent($task);

        if (!$agent) {
            return null;
        }

        $task->assignTo($agent);

        return $agent;
    }

    /**
     * 获取任务类型所需的能力列表
     */
    private function getRequiredCapabilities(string $taskType): array
    {
        return self::TYPE_CAPABILITY_MAP[$taskType] ?? [];
    }

    /**
     * 计算 Agent 的能力匹配分数
     *
     * - 2分：Agent 拥有所有所需能力
     * - 1分：Agent 拥有部分所需能力
     * - 0分：无所需能力（但如果是 'other' 类型，有任意能力即得 1 分）
     */
    private function calculateCapabilityScore(Agent $agent, array $requiredCaps): int
    {
        $agentCaps = $agent->capabilities ?? [];

        if (empty($requiredCaps)) {
            // other 类型：有任意能力就合格
            return !empty($agentCaps) ? 1 : 0;
        }

        $matched = array_intersect($requiredCaps, $agentCaps);
        $matchCount = count($matched);

        if ($matchCount === 0) {
            return 0;
        }

        return $matchCount >= count($requiredCaps) ? 2 : 1;
    }

    /**
     * 计算 Agent 的负载分数（归一化到 0-1 之间）
     *
     * 越空闲分数越高。公式: 1 - (current_tasks / max_capacity)
     * 容量为 1 时，空闲 = 1.0，满载 = 0.0
     */
    private function calculateLoadScore(Agent $agent): float
    {
        $capacity = max($agent->max_capacity, 1);

        return 1.0 - ($agent->current_tasks / $capacity);
    }
}
