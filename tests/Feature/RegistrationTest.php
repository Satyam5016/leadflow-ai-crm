<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_creates_owner_workspace(): void
    {
        Role::firstOrCreate(['name' => 'Owner']);

        $this->post('/register', [
            'name' => 'Asha Owner',
            'email' => 'asha@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'workspace_name' => 'Asha Ventures',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'asha@example.com')->first();
        $workspace = Workspace::where('name', 'Asha Ventures')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($workspace);
        $this->assertTrue($workspace->members()->whereKey($user)->exists());
        $this->assertSame('Owner', $user->workspaces()->first()->pivot->role);
    }
}
