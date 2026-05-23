<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Task;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'agent_id' => Agent::factory(),
            'type' => 'system',
            'content' => fake()->sentence(),
            'metadata' => null,
        ];
    }

    /** 系统消息（无 agent） */
    public function system(): static
    {
        return $this->state(fn() => [
            'type' => 'system',
            'agent_id' => null,
        ]);
    }
}
