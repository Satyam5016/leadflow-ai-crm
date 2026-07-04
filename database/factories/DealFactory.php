<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Deal> */
class DealFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'owner_id' => User::factory(),
            'title' => fake()->catchPhrase(),
            'stage' => fake()->randomElement(['prospecting', 'negotiation', 'proposal', 'won', 'lost']),
            'value' => fake()->numberBetween(5000, 150000),
            'expected_close_date' => fake()->dateTimeBetween('now', '+90 days'),
            'probability' => fake()->numberBetween(10, 95),
            'description' => fake()->paragraph(),
        ];
    }
}
