<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage_workspace', 'manage_users', 'manage_leads', 'manage_customers',
            'manage_deals', 'manage_tasks', 'view_reports', 'manage_settings',
        ];

        collect($permissions)->each(fn ($permission) => Permission::firstOrCreate(['name' => $permission]));

        collect(['Owner', 'Admin', 'Manager', 'Sales Executive', 'Viewer'])->each(function (string $role) use ($permissions) {
            Role::firstOrCreate(['name' => $role])->syncPermissions(match ($role) {
                'Owner', 'Admin' => $permissions,
                'Manager' => ['manage_leads', 'manage_customers', 'manage_deals', 'manage_tasks', 'view_reports'],
                'Sales Executive' => ['manage_leads', 'manage_customers', 'manage_deals', 'manage_tasks'],
                default => ['view_reports'],
            });
        });

        $owner = User::firstOrCreate(
            ['email' => 'owner@leadflow.test'],
            [
                'name' => 'Satyam Founder',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => str()->random(10),
            ],
        );

        $sales = User::firstOrCreate(
            ['email' => 'sales@leadflow.test'],
            [
                'name' => 'Priya Sales',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => str()->random(10),
            ],
        );

        $workspace = Workspace::firstOrCreate(
            ['slug' => 'acme-growth-studio'],
            ['owner_id' => $owner->id, 'name' => 'Acme Growth Studio'],
        );

        $workspace->members()->syncWithoutDetaching([
            $owner->id => ['role' => 'Owner', 'joined_at' => now()],
            $sales->id => ['role' => 'Sales Executive', 'joined_at' => now()],
        ]);
        $owner->assignRole('Owner');
        $sales->assignRole('Sales Executive');

        if ($workspace->leads()->doesntExist()) {
            Lead::factory()->count(14)->create(['workspace_id' => $workspace->id, 'assigned_to_id' => $sales->id]);
        }

        if ($workspace->customers()->doesntExist()) {
            Customer::factory()->count(8)->create(['workspace_id' => $workspace->id, 'owner_id' => $sales->id]);
        }

        if ($workspace->deals()->doesntExist()) {
            $customers = $workspace->customers()->get();
            Deal::factory()->count(16)->create(['workspace_id' => $workspace->id, 'owner_id' => $sales->id])->each(function (Deal $deal) use ($customers) {
                $deal->update(['customer_id' => $customers->random()->id]);
            });
        }

        if ($workspace->tasks()->doesntExist()) {
            Task::factory()->count(18)->create(['workspace_id' => $workspace->id, 'assigned_to_id' => $sales->id, 'created_by_id' => $owner->id]);
        }

        ActivityLog::firstOrCreate(
            ['workspace_id' => $workspace->id, 'event' => 'workspace.seeded'],
            ['user_id' => $owner->id, 'description' => 'Demo CRM workspace created with sales data.'],
        );
    }
}
