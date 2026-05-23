<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlmClient
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $config = config('agent-worker.llm');
        $this->apiKey = $config['api_key'];
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->model = $config['model'];
        $this->maxTokens = $config['max_tokens'];
        $this->temperature = $config['temperature'];
    }

    /**
     * 调用 LLM 并返回完整响应文本。
     */
    public function chat(array $messages, ?array $overrides = []): string
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ], $overrides);

        $url = "{$this->baseUrl}/chat/completions";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(120)->post($url, $payload);

        if ($response->failed()) {
            Log::error('LLM API 调用失败', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException(
                "LLM API 返回错误 [{$response->status()}]: {$response->body()}"
            );
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * 检查 LLM 连接是否可用（发送一条简单测试消息）。
     */
    public function ping(): bool
    {
        try {
            $this->chat([
                ['role' => 'user', 'content' => 'ping'],
            ], ['max_tokens' => 10]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 流式调用 LLM，逐 chunk 回调。（后续可扩展）
     */
    public function chatStream(array $messages, callable $onChunk, ?array $overrides = []): string
    {
        // 暂用非流式实现，后续可升级为 SSE 流
        $result = $this->chat($messages, $overrides);
        $onChunk($result);
        return $result;
    }
}
