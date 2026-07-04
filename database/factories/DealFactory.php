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
            'title' => $this->faker->catchPhrase(),
            'stage' => $this->faker->randomElement(['prospecting', 'negotiation', 'proposal', 'won', 'lost']),
            'value' => $this->faker->numberBetween(5000, 150000),
            'expected_close_date' => $this->faker->dateTimeBetween('now', '+90 days'),
            'probability' => $this->faker->numberBetween(10, 95),
            'description' => $this->faker->paragraph(),
        ];
    }
}
