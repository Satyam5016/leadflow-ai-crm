<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_lead_from_another_workspace(): void
    {
        [$user, $workspace] = $this->member('Owner');
        [$otherUser, $otherWorkspace] = $this->member('Owner');
        $lead = Lead::factory()->create(['workspace_id' => $otherWorkspace->id, 'assigned_to_id' => $otherUser->id]);

        $this->actingAs($user)->withSession(['workspace_id' => $workspace->id])->get(route('leads.show', $lead))->assertForbidden();
    }

    private function member(string $role): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $workspace->members()->attach($user, ['role' => $role, 'joined_at' => now()]);

        return [$user, $workspace];
    }
}
