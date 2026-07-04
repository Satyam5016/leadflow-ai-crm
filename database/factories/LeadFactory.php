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
            'name' => $this->faker->name(),
            'company' => $this->faker->company(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'status' => $this->faker->randomElement(['new', 'contacted', 'qualified', 'lost']),
            'source' => $this->faker->randomElement(['website', 'referral', 'email', 'LinkedIn', 'cold call']),
            'value' => $this->faker->numberBetween(1000, 50000),
            'notes' => $this->faker->sentence(),
            'ai_score' => $this->faker->numberBetween(30, 95),
            'ai_reason' => 'Seeded score based on source and profile completeness.',
        ];
    }
}
