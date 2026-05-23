<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['code', 'analysis', 'design', 'review', 'other']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => 'pending',
            'assigned_agent_id' => null,
        ];
    }

    /** @return static */
    public function processing(?Agent $agent = null): static
    {
        return $this->state(fn() => [
            'status' => 'processing',
            'assigned_agent_id' => $agent?->id ?? Agent::factory(),
            'started_at' => now(),
        ]);
    }

    /** @return static */
    public function completed(): static
    {
        return $this->state(fn() => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /** @return static */
    public function failed(): static
    {
        return $this->state(fn() => [
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }
}
