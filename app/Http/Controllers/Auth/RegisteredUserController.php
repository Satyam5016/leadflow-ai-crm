<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'workspace_name' => ['required', 'string', 'max:255'],
        ]);

        [$user, $workspace] = DB::transaction(function () use ($validated) {
            $user = User::create($validated);
            $workspace = Workspace::create([
                'name' => $validated['workspace_name'],
                'slug' => Str::slug($validated['workspace_name']).'-'.Str::lower(Str::random(5)),
                'owner_id' => $user->id,
            ]);
            $workspace->members()->attach($user, ['role' => 'Owner', 'joined_at' => now()]);
            Role::firstOrCreate(['name' => 'Owner']);
            $user->assignRole('Owner');

            return [$user, $workspace];
        });

        event(new Registered($user));
        Auth::login($user);
        session(['workspace_id' => $workspace->id]);

        return redirect()->route('dashboard');
    }
}
