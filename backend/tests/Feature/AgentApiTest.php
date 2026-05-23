<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_可以列出所有_agent(): void
    {
        Agent::factory(3)->create();

        $response = $this->getJson('/api/v1/agents');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_可以按状态筛选_agent(): void
    {
        Agent::factory()->create(['status' => 'online']);
        Agent::factory()->offline()->create();

        $response = $this->getJson('/api/v1/agents?status=online');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'online');
    }

    public function test_可以创建_agent(): void
    {
        $payload = [
            'name' => '测试 Agent',
            'description' => '测试描述',
            'capabilities' => ['code', 'analysis'],
            'status' => 'online',
            'max_capacity' => 10,
        ];

        $response = $this->postJson('/api/v1/agents', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', '测试 Agent')
            ->assertJsonPath('data.capabilities', ['code', 'analysis']);
        $this->assertDatabaseHas('agents', ['name' => '测试 Agent']);
    }

    public function test_可以查看单个_agent(): void
    {
        $agent = Agent::factory()->create(['name' => '查看测试']);

        $response = $this->getJson("/api/v1/agents/{$agent->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', '查看测试');
    }

    public function test_可以更新_agent(): void
    {
        $agent = Agent::factory()->create(['name' => '旧名称']);

        $response = $this->putJson("/api/v1/agents/{$agent->id}", [
            'name' => '新名称',
            'capabilities' => ['design'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', '新名称');
        $this->assertDatabaseHas('agents', ['name' => '新名称']);
    }

    public function test_可以删除_agent(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->deleteJson("/api/v1/agents/{$agent->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted($agent);
    }

    public function test_删除有进行中任务的_agent_返回_409(): void
    {
        $agent = Agent::factory()->full()->create();

        $response = $this->deleteJson("/api/v1/agents/{$agent->id}");

        $response->assertStatus(409)
            ->assertJsonPath('message', fn(string $m) => str_contains($m, '进行中'));
    }

    public function test_未认证用户无法访问_agent_api(): void
    {
        $this->postJson('/api/v1/agents', ['name' => 'test'])
            ->assertStatus(401);
    }
}
