<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDealRequest;
use App\Models\Deal;
use App\Models\User;
use App\Notifications\CrmAssignmentNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');
        $query = $workspace->deals()->with(['customer:id,name,company_name', 'owner:id,name']);
        $query->when($request->filled('stage'), fn ($q) => $q->where('stage', $request->string('stage')));
        $query->when($request->filled('assigned_to_id'), fn ($q) => $q->where('owner_id', $request->integer('assigned_to_id')));
        $query->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')));
        $query->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')));
        $query->when($request->filled('q'), function ($q) use ($request) {
            $search = '%'.$request->string('q')->toString().'%';
            $q->where('title', 'like', $search);
        });
        $sort = in_array($request->string('sort')->toString(), ['title', 'stage', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'created_at';

        return Inertia::render('Deals/Pipeline', [
            'deals' => $query->orderBy($sort, $sort === 'created_at' ? 'desc' : 'asc')->get()->groupBy('stage'),
            'customers' => $workspace->customers()->select('id', 'name', 'company_name')->get(),
            'members' => $workspace->members()->select('users.id', 'name')->get(),
            'filters' => $request->only(['stage', 'assigned_to_id', 'from', 'to', 'q']),
        ]);
    }

    public function store(StoreDealRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $workspace = $request->attributes->get('workspace');
        $deal = Deal::create(['workspace_id' => $workspace->id] + $request->validated());
        $logger->log($workspace, 'deal.created', "Deal {$deal->title} created.", $deal);
        User::find($deal->owner_id)?->notify(new CrmAssignmentNotification('New deal assigned', $deal->title, route('deals.index')));

        return back()->with('success', 'Deal created.');
    }

    public function show(Deal $deal): Response
    {
        $this->authorize('view', $deal);

        return Inertia::render('Deals/Show', [
            'deal' => $deal->load(['customer:id,name,company_name', 'owner:id,name', 'activities.user:id,name', 'notes.user:id,name']),
            'tasks' => $deal->tasks()->with('assignedTo:id,name')->get(),
            'emails' => $deal->emails()->latest()->get(),
            'files' => $deal->files()->latest()->get(),
        ]);
    }

    public function update(StoreDealRequest $request, Deal $deal, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('update', $deal);
        $oldStage = $deal->stage;
        $deal->update($request->validated());
        $logger->log($deal->workspace, 'deal.updated', "Deal {$deal->title} updated.", $deal, ['old_stage' => $oldStage, 'new_stage' => $deal->stage]);

        return back()->with('success', 'Deal updated.');
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        $this->authorize('delete', $deal);
        $deal->delete();

        return back()->with('success', 'Deal deleted.');
    }

    public function stage(Request $request, Deal $deal, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('update', $deal);
        $validated = $request->validate(['stage' => ['required', 'in:prospecting,negotiation,proposal,won,lost']]);
        $oldStage = $deal->stage;
        $deal->update(['stage' => $validated['stage']]);
        $logger->log($deal->workspace, 'deal.stage_changed', "Deal {$deal->title} moved to {$deal->stage}.", $deal, ['old_stage' => $oldStage, 'new_stage' => $deal->stage]);

        return back()->with('success', 'Deal stage updated.');
    }
}
