<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Task;
use App\Console\Commands\CheckTaskTimeouts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTimeoutCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_超时任务被标记为失败(): void
    {
        $agent = Agent::factory()->create(['current_tasks' => 1]);
        Task::factory()->processing($agent)->create([
            'started_at' => now()->subMinutes(35), // 超时
        ]);

        $this->artisan('tasks:check-timeouts --timeout=30')
            ->assertSuccessful();

        $task = Task::first();
        $this->assertEquals('failed', $task->status);
        $this->assertNotNull($task->completed_at);

        // Agent 任务计数已减少
        $agent->refresh();
        $this->assertEquals(0, $agent->current_tasks);

        // 有系统消息
        $this->assertDatabaseHas('messages', [
            'task_id' => $task->id,
            'type' => 'system',
        ]);
    }

    public function test_未超时任务不会被标记(): void
    {
        $agent = Agent::factory()->create(['current_tasks' => 1]);
        Task::factory()->processing($agent)->create([
            'started_at' => now()->subMinutes(10), // 未超时
        ]);

        $this->artisan('tasks:check-timeouts --timeout=30')
            ->assertSuccessful();

        $this->assertEquals('processing', Task::first()->status);
    }

    public function test_非_processing_任务不受影响(): void
    {
        Task::factory()->create([
            'status' => 'pending',
            'started_at' => now()->subHours(2),
        ]);
        Task::factory()->completed()->create([
            'started_at' => now()->subHours(2),
        ]);

        $this->artisan('tasks:check-timeouts --timeout=30')
            ->assertSuccessful();

        $this->assertEquals('pending', Task::first()->status);
    }
}
