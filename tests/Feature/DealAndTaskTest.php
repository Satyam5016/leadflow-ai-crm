<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealAndTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_deal_and_assign_task(): void
    {
        [$user, $workspace] = $this->member('Owner');
        $customer = Customer::factory()->create(['workspace_id' => $workspace->id, 'owner_id' => $user->id]);

        $this->actingAs($user)->withSession(['workspace_id' => $workspace->id])->post('/deals', [
            'title' => 'Annual CRM Rollout',
            'customer_id' => $customer->id,
            'lead_id' => null,
            'owner_id' => $user->id,
            'stage' => 'proposal',
            'value' => 75000,
            'expected_close_date' => now()->addMonth()->toDateString(),
            'probability' => 70,
            'description' => 'Expansion opportunity.',
        ])->assertRedirect();

        $this->assertDatabaseHas('deals', ['title' => 'Annual CRM Rollout', 'workspace_id' => $workspace->id]);

        $this->actingAs($user)->withSession(['workspace_id' => $workspace->id])->post('/tasks', [
            'title' => 'Send proposal',
            'description' => 'Send pricing and implementation plan.',
            'assigned_to_id' => $user->id,
            'due_date' => now()->addDays(2)->toDateTimeString(),
            'priority' => 'high',
            'status' => 'pending',
        ])->assertRedirect();

        $this->assertDatabaseHas('tasks', ['title' => 'Send proposal', 'workspace_id' => $workspace->id]);
    }

    public function test_viewer_cannot_create_deal(): void
    {
        [$user, $workspace] = $this->member('Viewer');

        $this->actingAs($user)->withSession(['workspace_id' => $workspace->id])->post('/deals', [
            'title' => 'Blocked Deal',
            'stage' => 'prospecting',
            'value' => 1000,
            'probability' => 20,
        ])->assertForbidden();

        $this->assertDatabaseMissing('deals', ['title' => 'Blocked Deal']);
    }

    private function member(string $role): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $workspace->members()->attach($user, ['role' => $role, 'joined_at' => now()]);

        return [$user, $workspace];
    }
}
