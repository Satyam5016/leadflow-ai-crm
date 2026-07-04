<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Workspaces/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $workspace = Workspace::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
            'owner_id' => $request->user()->id,
        ]);

        $workspace->members()->attach($request->user(), ['role' => 'Owner', 'joined_at' => now()]);
        session(['workspace_id' => $workspace->id]);

        return redirect()->route('dashboard')->with('success', 'Workspace created.');
    }

    public function edit(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->workspaces()->whereKey($workspace)->exists(), 403);

        return Inertia::render('Workspaces/Settings', [
            'settingsWorkspace' => $workspace,
            'members' => $workspace->members()->select('users.id', 'name', 'email')->get(),
            'invitations' => Invitation::where('workspace_id', $workspace->id)->latest()->get(),
        ]);
    }

    public function update(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($request->user()->canInWorkspace('manage_workspace', $workspace), 403);
        $workspace->update($request->validate(['name' => ['required', 'string', 'max:255']]));

        return back()->with('success', 'Workspace updated.');
    }

    public function switch(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($request->user()->workspaces()->whereKey($workspace)->exists(), 403);
        session(['workspace_id' => $workspace->id]);

        return redirect()->route('dashboard');
    }
}
