<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->agent = Agent::factory()->create();
    }

    // ─── 基础 CRUD ───────────────────────────────

    public function test_可以列出所有任务(): void
    {
        Task::factory(3)->create();

        $response = $this->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_可以按状态筛选任务(): void
    {
        Task::factory()->create(['status' => 'pending']);
        Task::factory()->completed()->create();

        $response = $this->getJson('/api/v1/tasks?status=pending');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_可以按优先级筛选任务(): void
    {
        Task::factory()->create(['priority' => 'high']);
        Task::factory()->create(['priority' => 'low']);

        $response = $this->getJson('/api/v1/tasks?priority=high');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_可以创建任务(): void
    {
        $payload = [
            'title' => '测试任务',
            'description' => '任务描述',
            'type' => 'code',
            'priority' => 'high',
        ];

        $response = $this->postJson('/api/v1/tasks', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.title', '测试任务')
            ->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('tasks', ['title' => '测试任务']);
    }

    public function test_创建任务时可自动指派(): void
    {
        $this->agent->update(['capabilities' => ['code']]);

        $response = $this->postJson('/api/v1/tasks', [
            'title' => '自动指派测试',
            'type' => 'code',
            'auto_assign' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonStructure(['data' => ['agent' => ['id', 'name']]]);
    }

    public function test_可以查看任务详情(): void
    {
        $task = Task::factory()->create(['title' => '详情测试']);

        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('data.title', '详情测试');
    }

    public function test_可以更新任务(): void
    {
        $task = Task::factory()->create(['title' => '旧标题']);

        $response = $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => '新标题',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', '新标题');
    }

    public function test_可以删除任务(): void
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($task);
    }

    // ─── 状态流转 ────────────────────────────────

    public function test_可以指派_agent(): void
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/assign", [
            'agent_id' => $this->agent->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.agent.id', $this->agent->id);
        $this->assertDatabaseHas('agents', [
            'id' => $this->agent->id,
            'current_tasks' => 1,
        ]);
    }

    public function test_任务可以流转为_completed(): void
    {
        $task = Task::factory()->processing($this->agent)->create();

        $response = $this->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');
        $this->assertDatabaseHas('agents', [
            'id' => $this->agent->id,
            'current_tasks' => 0,
        ]);
    }

    public function test_任务可以流转为_failed(): void
    {
        $task = Task::factory()->processing($this->agent)->create();

        $response = $this->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => 'failed',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'failed');
    }

    public function test_任务可以流转为_cancelled(): void
    {
        $task = Task::factory()->processing($this->agent)->create();

        $response = $this->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('agents', [
            'id' => $this->agent->id,
            'current_tasks' => 0,
        ]);
    }

    public function test_无效状态流转返回_422(): void
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertStatus(422);
    }

    public function test_未认证用户无法访问_task_api(): void
    {
        $this->postJson('/api/v1/tasks', ['title' => 'test'])
            ->assertStatus(401);
    }
}
