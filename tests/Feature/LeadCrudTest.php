<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_update_and_delete_lead_in_workspace(): void
    {
        [$user, $workspace] = $this->member('Owner');

        $payload = [
            'name' => 'Maya Rao',
            'company' => 'Northstar Labs',
            'email' => 'maya@northstar.test',
            'phone' => '555-0100',
            'status' => 'new',
            'source' => 'website',
            'value' => 12000,
            'assigned_to_id' => $user->id,
            'notes' => 'Requested urgent demo and budget review.',
        ];

        $this->actingAs($user)->withSession(['workspace_id' => $workspace->id])->post('/leads', $payload)->assertRedirect();
        $lead = Lead::where('email', 'maya@northstar.test')->firstOrFail();

        $this->actingAs($user)->withSession(['workspace_id' => $workspace->id])->patch(route('leads.update', $lead), [...$payload, 'status' => 'qualified'])->assertRedirect();
        $this->assertSame('qualified', $lead->fresh()->status);

        $this->actingAs($user)->withSession(['workspace_id' => $workspace->id])->delete(route('leads.destroy', $lead))->assertRedirect();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    private function member(string $role): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $workspace->members()->attach($user, ['role' => $role, 'joined_at' => now()]);

        return [$user, $workspace];
    }
}
