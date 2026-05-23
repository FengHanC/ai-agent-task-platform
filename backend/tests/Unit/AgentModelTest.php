<?php

namespace Tests\Unit;

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_更新时间戳(): void
    {
        $agent = Agent::factory()->create(['last_heartbeat_at' => null]);

        $agent->heartbeat();

        $agent->refresh();
        $this->assertNotNull($agent->last_heartbeat_at);
    }

    public function test_is_heartbeat_expired_判断超时(): void
    {
        $agent = Agent::factory()->create();
        $agent->heartbeat();

        $this->assertFalse($agent->isHeartbeatExpired(120));
    }

    public function test_mark_offline_下线(): void
    {
        $agent = Agent::factory()->create();

        $agent->markOffline();

        $agent->refresh();
        $this->assertEquals('offline', $agent->status);
    }

    public function test_increment_task_count_增加任务计数(): void
    {
        $agent = Agent::factory()->create(['current_tasks' => 0]);

        $agent->incrementTaskCount();

        $agent->refresh();
        $this->assertEquals(1, $agent->current_tasks);
    }

    public function test_decrement_task_count_减少任务计数(): void
    {
        $agent = Agent::factory()->create(['current_tasks' => 3]);

        $agent->decrementTaskCount();

        $agent->refresh();
        $this->assertEquals(2, $agent->current_tasks);
    }

    public function test_decrement_task_count_不会低于_0(): void
    {
        $agent = Agent::factory()->create(['current_tasks' => 0]);

        $agent->decrementTaskCount();

        $agent->refresh();
        $this->assertEquals(0, $agent->current_tasks);
    }
}
