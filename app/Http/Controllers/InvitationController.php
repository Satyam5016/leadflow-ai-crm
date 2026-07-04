<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Workspace;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function store(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($request->user()->canInWorkspace('manage_users', $workspace), 403);
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:Admin,Manager,Sales Executive,Viewer'],
        ]);

        $invitation = Invitation::updateOrCreate(
            ['workspace_id' => $workspace->id, 'email' => $validated['email']],
            ['invited_by_id' => $request->user()->id, 'role' => $validated['role'], 'token' => Str::random(40)]
        );
        Notification::route('mail', $invitation->email)->notify(new WorkspaceInvitationNotification($invitation));

        return back()->with('success', 'Invitation email sent.');
    }

    public function accept(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_unless($request->user()->email === $invitation->email, 403);
        $invitation->workspace->members()->syncWithoutDetaching([
            $request->user()->id => ['role' => $invitation->role, 'joined_at' => now()],
        ]);
        $invitation->update(['accepted_at' => now()]);
        session(['workspace_id' => $invitation->workspace_id]);

        return redirect()->route('dashboard')->with('success', 'Invitation accepted.');
    }
}
