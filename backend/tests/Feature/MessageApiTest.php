<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->task = Task::factory()->create();
    }

    public function test_可以列出任务消息(): void
    {
        $this->task->addMessage('消息1');
        $this->task->addMessage('消息2');

        $response = $this->getJson("/api/v1/tasks/{$this->task->id}/messages");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.content', '消息1');
    }

    public function test_可以创建消息(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/messages", [
            'content' => '新消息',
            'type' => 'user',
            'agent_id' => $agent->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.content', '新消息');
    }

    public function test_消息按时间倒序排列(): void
    {
        $this->task->addMessage('旧消息', 'system', null, null);
        $this->travel(1)->minutes();
        $this->task->addMessage('新消息', 'system', null, null);

        $response = $this->getJson("/api/v1/tasks/{$this->task->id}/messages");

        $response->assertOk()
            ->assertJsonPath('data.0.content', '新消息')
            ->assertJsonPath('data.1.content', '旧消息');
    }

    public function test_未认证用户无法访问消息_api(): void
    {
        $this->postJson("/api/v1/tasks/{$this->task->id}/messages", ['content' => 'hack'])
            ->assertStatus(401);
    }
}
