<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LLM Provider Configuration
    |--------------------------------------------------------------------------
    |
    | 配置 Agent Worker 使用的 LLM 提供商。
    | 兼容任何 OpenAI-compatible API（OpenAI、OpenRouter、Ollama、vLLM 等）。
    |
    */
    'llm' => [
        'provider' => env('AGENT_LLM_PROVIDER', 'openai'),
        'api_key' => env('AGENT_LLM_API_KEY', ''),
        'base_url' => env('AGENT_LLM_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('AGENT_LLM_MODEL', 'gpt-4o-mini'),
        'max_tokens' => (int) env('AGENT_LLM_MAX_TOKENS', 4096),
        'temperature' => (float) env('AGENT_LLM_TEMPERATURE', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Worker Behavior
    |--------------------------------------------------------------------------
    |
    | 控制 Worker 的调度行为和并发限制。
    |
    */
    'worker' => [
        // 每次调度最多处理几个任务（避免一次跑太久）
        'max_tasks_per_run' => (int) env('AGENT_WORKER_MAX_TASKS', 3),
        // 系统提示词模板（{name}, {capabilities} 会被替换）
        'system_prompt_template' => env(
            'AGENT_SYSTEM_PROMPT',
            '你是一个 AI Agent，名称是 "{name}"。你的能力包括：{capabilities}。'
            . '请根据任务要求给出完整的处理结果。'
        ),
        // 用户提示词模板（{title}, {description}, {type} 会被替换）
        'user_prompt_template' => env(
            'AGENT_USER_PROMPT',
            "## 任务\n标题：{title}\n描述：{description}\n类型：{type}\n\n请处理这个任务并输出结果。"
        ),
    ],
];
