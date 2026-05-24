<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Task;
use App\Services\AgentWorkerService;
use App\Services\LlmClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AgentWorkerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LlmClient|MockInterface $llmMock;
    private AgentWorkerService $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llmMock = Mockery::mock(LlmClient::class);
        $this->worker = new AgentWorkerService($this->llmMock);

        config(['agent-worker.worker.system_prompt_template' => '你是 {name}，能力：{capabilities}']);
        config(['agent-worker.worker.user_prompt_template' => '任务：{title} - {description}']);
    }

    public function test_无可用的_agent_返回_0(): void
    {
        $count = $this->worker->run();

        $this->assertEquals(0, $count);
    }

    public function test_有_agent_无待处理任务_返回_0(): void
    {
        Agent::factory()->create(['status' => 'online', 'capabilities' => ['code']]);

        $count = $this->worker->run();

        $this->assertEquals(0, $count);
    }

    public function test_处理一个完整任务(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'online',
            'capabilities' => ['code'],
        ]);
        $task = Task::factory()->create([
            'status' => 'pending',
            'type' => 'code',
            'title' => '写一个 Hello World',
        ]);

        $this->llmMock->shouldReceive('chat')
            ->once()
            ->with(Mockery::on(function (array $messages) {
                return str_contains($messages[0]['content'], '写一个 Hello World');
            }))
            ->andReturn('```python\nprint("Hello World")\n```');

        $count = $this->worker->run();

        $this->assertEquals(1, $count);

        // 任务应标记完成
        $task->refresh();
        $this->assertEquals('completed', $task->status);

        // Agent 任务计数应归零
        $agent->refresh();
        $this->assertEquals(0, $agent->current_tasks);

        // 有 LLM 结果消息
        $this->assertDatabaseHas('messages', [
            'task_id' => $task->id,
            'type' => 'agent',
        ]);
    }

    public function test_LLM_调用失败时标记失败(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'online',
            'capabilities' => ['code'],
        ]);
        $task = Task::factory()->create([
            'status' => 'pending',
            'type' => 'code',
        ]);

        $this->llmMock->shouldReceive('chat')
            ->once()
            ->andThrow(new \RuntimeException('API 超时'));

        $count = $this->worker->run();

        $this->assertEquals(1, $count);

        $task->refresh();
        $this->assertEquals('failed', $task->status);

        // 有错误消息
        $this->assertDatabaseHas('messages', [
            'task_id' => $task->id,
            'type' => 'error',
        ]);
    }

    public function test_只找能力匹配的任务(): void
    {
        $codeAgent = Agent::factory()->create([
            'status' => 'online',
            'capabilities' => ['code'],
        ]);
        // design 类型的任务，code Agent 不匹配
        Task::factory()->create([
            'status' => 'pending',
            'type' => 'design',
        ]);

        // 没有匹配到的任务
        $this->llmMock->shouldReceive('chat')->never();

        $count = $this->worker->run();

        $this->assertEquals(0, $count);
    }

    public function test_尊重_max_tasks_限制(): void
    {
        Agent::factory(2)->create([
            'status' => 'online',
            'capabilities' => ['code'],
            'current_tasks' => 0,
        ]);
        Task::factory(5)->create([
            'status' => 'pending',
            'type' => 'code',
        ]);

        $this->llmMock->shouldReceive('chat')
            ->times(3)
            ->andReturn('ok');

        $count = $this->worker->run(3);

        $this->assertEquals(3, $count);
    }

    // ─── processReply 测试 ────────────────────────────

    public function test_process_reply_回复用户消息(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'online',
            'capabilities' => ['code'],
        ]);
        $task = Task::factory()->processing($agent)->create();
        $task->addMessage('请帮我优化这段代码', 'user', null);

        // 新发一条 user 消息触发回复
        $userMsg = $task->addMessage('再检查一下性能', 'user', null);

        $this->llmMock->shouldReceive('chat')
            ->once()
            ->with(Mockery::on(function (array $messages) {
                // 应包含对话上下文（最近消息）
                $contents = array_column($messages, 'content');
                $msgText = implode(' ', $contents);
                return str_contains($msgText, '性能') || str_contains($msgText, '优化');
            }))
            ->andReturn('性能检查完成，代码无问题。');

        $this->worker->processReply($task, $userMsg);

        // 应有 agent 消息写入
        $this->assertDatabaseHas('messages', [
            'task_id' => $task->id,
            'type' => 'agent',
            'agent_id' => $agent->id,
        ]);

        // 应有 "正在回复" 的系统提示
        $this->assertDatabaseHas('messages', [
            'task_id' => $task->id,
            'type' => 'system',
            'agent_id' => $agent->id,
        ]);
    }

    public function test_process_reply_无_agent_不处理(): void
    {
        $task = Task::factory()->create(['status' => 'processing']);
        $msg = $task->addMessage('hello', 'user', null);

        // 不发任何 LLM 调用
        $this->llmMock->shouldReceive('chat')->never();

        $this->worker->processReply($task, $msg);
    }

    public function test_process_reply_LLM_失败时写错误消息(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'online',
            'capabilities' => ['code'],
        ]);
        $task = Task::factory()->processing($agent)->create();
        $msg = $task->addMessage('help', 'user', null);

        $this->llmMock->shouldReceive('chat')
            ->once()
            ->andThrow(new \RuntimeException('API 不可达'));

        $this->worker->processReply($task, $msg);

        // 有错误消息
        $this->assertDatabaseHas('messages', [
            'task_id' => $task->id,
            'type' => 'error',
        ]);
    }
}
