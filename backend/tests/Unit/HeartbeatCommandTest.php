<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Console\Commands\CheckAgentHeartbeats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeartbeatCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_超时_agent_被下线(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'online',
            'last_heartbeat_at' => now()->subMinutes(5),
        ]);

        $this->artisan('agents:check-heartbeats --timeout=120')
            ->assertSuccessful();

        $agent->refresh();
        $this->assertEquals('offline', $agent->status);
    }

    public function test_活跃_agent_不被下线(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'online',
            'last_heartbeat_at' => now()->subMinute(),
        ]);

        $this->artisan('agents:check-heartbeats --timeout=120')
            ->assertSuccessful();

        $agent->refresh();
        $this->assertEquals('online', $agent->status);
    }

    public function test_离线_agent_不受影响(): void
    {
        $agent = Agent::factory()->offline()->create([
            'last_heartbeat_at' => now()->subHours(2),
        ]);

        $this->artisan('agents:check-heartbeats --timeout=120')
            ->assertSuccessful();

        $agent->refresh();
        $this->assertEquals('offline', $agent->status);
    }
}
