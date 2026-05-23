<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'capabilities' => fake()->randomElements(['code', 'analysis', 'design', 'review', 'other'], rand(1, 3)),
            'status' => 'online',
            'max_tasks' => 5,
            'current_tasks' => 0,
        ];
    }

    /** 指定能力集合 */
    public function withCapabilities(array $caps): static
    {
        return $this->state(fn() => ['capabilities' => $caps]);
    }

    /** 设置离线状态 */
    public function offline(): static
    {
        return $this->state(fn() => ['status' => 'offline']);
    }

    /** 设置忙碌状态 */
    public function busy(): static
    {
        return $this->state(fn() => ['status' => 'busy']);
    }

    /** 达到最大任务数 */
    public function full(): static
    {
        return $this->state(fn() => ['current_tasks' => 5, 'max_tasks' => 5]);
    }
}
