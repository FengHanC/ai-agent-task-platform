<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Message;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_message_创建关联消息(): void
    {
        $task = Task::factory()->create();

        $msg = $task->addMessage('测试消息', 'system');

        $this->assertInstanceOf(Message::class, $msg);
        $this->assertEquals('测试消息', $msg->content);
        $this->assertEquals('system', $msg->type);
        $this->assertEquals($task->id, $msg->task_id);
    }

    public function test_assign_to_指派_agent(): void
    {
        $task = Task::factory()->create(['status' => 'pending']);
        $agent = Agent::factory()->create(['current_tasks' => 0]);

        $task->assignTo($agent);

        $task->refresh();
        $this->assertEquals('processing', $task->status);
        $this->assertEquals($agent->id, $task->assigned_agent_id);
        $this->assertNotNull($task->started_at);

        $agent->refresh();
        $this->assertEquals(1, $agent->current_tasks);
    }

    public function test_mark_completed_完成任务(): void
    {
        $agent = Agent::factory()->create(['current_tasks' => 1]);
        $task = Task::factory()->processing($agent)->create();

        $task->markCompleted();

        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completed_at);

        $agent->refresh();
        $this->assertEquals(0, $agent->current_tasks);
    }

    public function test_mark_failed_标记失败(): void
    {
        $agent = Agent::factory()->create(['current_tasks' => 1]);
        $task = Task::factory()->processing($agent)->create();

        $task->markFailed();

        $task->refresh();
        $this->assertEquals('failed', $task->status);

        $agent->refresh();
        $this->assertEquals(0, $agent->current_tasks);
    }

    public function test_mark_cancelled_取消进行中任务(): void
    {
        $agent = Agent::factory()->create(['current_tasks' => 1]);
        $task = Task::factory()->processing($agent)->create();

        $task->markCancelled();

        $task->refresh();
        $this->assertEquals('cancelled', $task->status);
        $this->assertNotNull($task->completed_at);

        $agent->refresh();
        $this->assertEquals(0, $agent->current_tasks);
    }

    public function test_mark_cancelled_取消待处理任务(): void
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $task->markCancelled();

        $task->refresh();
        $this->assertEquals('cancelled', $task->status);
    }

    public function test_mark_timed_out_超时失败(): void
    {
        $agent = Agent::factory()->create(['current_tasks' => 1]);
        $task = Task::factory()->processing($agent)->create();

        $task->markTimedOut();

        $task->refresh();
        $this->assertEquals('failed', $task->status);
        $this->assertNotNull($task->completed_at);

        $agent->refresh();
        $this->assertEquals(0, $agent->current_tasks);

        // 应该有系统消息
        $this->assertDatabaseHas('messages', [
            'task_id' => $task->id,
            'type' => 'system',
        ]);
    }
}
