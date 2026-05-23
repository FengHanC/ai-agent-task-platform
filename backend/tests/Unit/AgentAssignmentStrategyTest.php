<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\Task;
use App\Services\AgentAssignmentStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentAssignmentStrategyTest extends TestCase
{
    use RefreshDatabase;

    private AgentAssignmentStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new AgentAssignmentStrategy();
    }

    public function test_选择能力匹配的_agent(): void
    {
        $codeAgent = Agent::factory()->withCapabilities(['code'])->create();
        Agent::factory()->withCapabilities(['design'])->create();

        $task = Task::factory()->create(['type' => 'code']);

        $selected = $this->strategy->assign($task);

        $this->assertNotNull($selected);
        $this->assertEquals($codeAgent->id, $selected->id);
    }

    public function test_选择负载最低的_agent(): void
    {
        Agent::factory()->withCapabilities(['code'])->create([
            'current_tasks' => 4, 'max_tasks' => 5,
        ]);
        $lightAgent = Agent::factory()->withCapabilities(['code'])->create([
            'current_tasks' => 1, 'max_tasks' => 5,
        ]);

        $task = Task::factory()->create(['type' => 'code']);

        $selected = $this->strategy->assign($task);

        $this->assertEquals($lightAgent->id, $selected->id);
    }

    public function test_没有可用_agent_返回_null(): void
    {
        $task = Task::factory()->create(['type' => 'code']);

        $selected = $this->strategy->assign($task);

        $this->assertNull($selected);
    }

    public function test_排除了_offline_的_agent(): void
    {
        Agent::factory()->withCapabilities(['code'])->offline()->create();

        $task = Task::factory()->create(['type' => 'code']);

        $selected = $this->strategy->assign($task);

        $this->assertNull($selected);
    }

    public function test_排除了任务满载的_agent(): void
    {
        Agent::factory()->withCapabilities(['code'])->full()->create();

        $task = Task::factory()->create(['type' => 'code']);

        $selected = $this->strategy->assign($task);

        $this->assertNull($selected);
    }
}
