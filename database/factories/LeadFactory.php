<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'assigned_to_id' => User::factory(),
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => fake()->randomElement(['new', 'contacted', 'qualified', 'lost']),
            'source' => fake()->randomElement(['website', 'referral', 'email', 'LinkedIn', 'cold call']),
            'value' => fake()->numberBetween(1000, 50000),
            'notes' => fake()->sentence(),
            'ai_score' => fake()->numberBetween(30, 95),
            'ai_reason' => 'Seeded score based on source and profile completeness.',
        ];
    }
}
