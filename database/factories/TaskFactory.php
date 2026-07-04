<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'assigned_to_id' => User::factory(),
            'created_by_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'due_date' => fake()->dateTimeBetween('now', '+21 days'),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => fake()->randomElement(['pending', 'in progress', 'completed']),
        ];
    }
}
