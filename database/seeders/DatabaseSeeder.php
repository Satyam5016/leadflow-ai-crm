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
            collect([
                ['Aarav Mehta', 'Nimbus Analytics', 'aarav@nimbus.test', 'website', 'qualified', 68000, 91],
                ['Isha Kapoor', 'BrightCart Retail', 'isha@brightcart.test', 'LinkedIn', 'contacted', 42000, 78],
                ['Rohan Singh', 'Northstar Labs', 'rohan@northstar.test', 'referral', 'new', 55000, 84],
                ['Maya Rao', 'Atlas Fintech', 'maya@atlas.test', 'email', 'qualified', 92000, 88],
                ['Kabir Shah', 'CloudNova Systems', 'kabir@cloudnova.test', 'cold call', 'contacted', 23000, 52],
                ['Naina Verma', 'UrbanHive', 'naina@urbanhive.test', 'website', 'new', 31000, 66],
                ['Dev Malhotra', 'GreenGrid Energy', 'dev@greengrid.test', 'referral', 'qualified', 125000, 94],
                ['Tara Joshi', 'EduSpark', 'tara@eduspark.test', 'LinkedIn', 'lost', 18000, 39],
            ])->each(function (array $lead) use ($workspace, $sales) {
                Lead::create([
                    'workspace_id' => $workspace->id,
                    'assigned_to_id' => $sales->id,
                    'name' => $lead[0],
                    'company' => $lead[1],
                    'email' => $lead[2],
                    'phone' => '+91 90000 '.str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT),
                    'source' => $lead[3],
                    'status' => $lead[4],
                    'value' => $lead[5],
                    'notes' => 'Demo lead seeded for portfolio walkthrough.',
                    'ai_score' => $lead[6],
                    'ai_reason' => 'Seeded score based on company fit, source quality, and stated interest.',
                ]);
            });
        }

        if ($workspace->customers()->doesntExist()) {
            collect([
                ['Neha Sharma', 'Zenith Robotics', 'neha@zenith.test'],
                ['Vikram Patel', 'PixelWave Media', 'vikram@pixelwave.test'],
                ['Ananya Das', 'PrimeOps Logistics', 'ananya@primeops.test'],
                ['Arjun Nair', 'BluePeak Consulting', 'arjun@bluepeak.test'],
                ['Meera Jain', 'HealthBridge', 'meera@healthbridge.test'],
            ])->each(function (array $customer) use ($workspace, $sales) {
                Customer::create([
                    'workspace_id' => $workspace->id,
                    'owner_id' => $sales->id,
                    'name' => $customer[0],
                    'company_name' => $customer[1],
                    'email' => $customer[2],
                    'phone' => '+91 98888 '.str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT),
                    'address' => 'Demo address for '.$customer[1],
                ]);
            });
        }

        if ($workspace->deals()->doesntExist()) {
            $customers = $workspace->customers()->get();
            collect([
                ['CRM rollout pilot', 'prospecting', 45000, 35],
                ['Annual sales suite', 'negotiation', 120000, 65],
                ['Marketing automation add-on', 'proposal', 38000, 72],
                ['Enterprise support renewal', 'won', 82000, 100],
                ['Regional expansion deal', 'lost', 27000, 0],
                ['AI assistant package', 'proposal', 56000, 80],
                ['Data migration project', 'negotiation', 34000, 60],
                ['Multi-team onboarding', 'prospecting', 76000, 40],
            ])->each(function (array $deal, int $index) use ($workspace, $sales, $customers) {
                Deal::create([
                    'workspace_id' => $workspace->id,
                    'customer_id' => $customers[$index % $customers->count()]->id,
                    'owner_id' => $sales->id,
                    'title' => $deal[0],
                    'stage' => $deal[1],
                    'value' => $deal[2],
                    'expected_close_date' => now()->addDays(15 + ($index * 7))->toDateString(),
                    'probability' => $deal[3],
                    'description' => 'Demo opportunity seeded for pipeline review.',
                ]);
            });
        }

        if ($workspace->tasks()->doesntExist()) {
            collect([
                ['Follow up with qualified leads', 'high', 'pending', 1],
                ['Send proposal to Atlas Fintech', 'high', 'in progress', 2],
                ['Prepare demo workspace', 'medium', 'pending', 3],
                ['Review lost deal reasons', 'medium', 'pending', 5],
                ['Call new website leads', 'low', 'completed', -1],
                ['Update pipeline probabilities', 'medium', 'in progress', 4],
            ])->each(function (array $task) use ($workspace, $owner, $sales) {
                Task::create([
                    'workspace_id' => $workspace->id,
                    'assigned_to_id' => $sales->id,
                    'created_by_id' => $owner->id,
                    'title' => $task[0],
                    'priority' => $task[1],
                    'status' => $task[2],
                    'due_date' => now()->addDays($task[3]),
                    'description' => 'Demo task seeded for the dashboard and reminders.',
                ]);
            });
        }

        ActivityLog::firstOrCreate(
            ['workspace_id' => $workspace->id, 'event' => 'workspace.seeded'],
            ['user_id' => $owner->id, 'description' => 'Demo CRM workspace created with sales data.'],
        );
    }
}
