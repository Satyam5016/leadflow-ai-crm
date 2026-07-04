<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\CrmAssignmentNotification;
use App\Services\AI\AILeadScoringService;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');
        $query = $workspace->leads()->with('assignedTo:id,name');

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')));
        $query->when($request->filled('assigned_to_id'), fn ($q) => $q->where('assigned_to_id', $request->integer('assigned_to_id')));
        $query->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')));
        $query->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')));
        $query->when($request->filled('q'), function ($q) use ($request) {
            $search = '%'.$request->string('q')->toString().'%';
            $q->where(fn ($inner) => $inner->where('name', 'like', $search)->orWhere('company', 'like', $search)->orWhere('email', 'like', $search));
        });
        $sort = in_array($request->string('sort')->toString(), ['name', 'company', 'status', 'source', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'created_at';

        return Inertia::render('Leads/Index', [
            'leads' => $query->orderBy($sort, $sort === 'created_at' ? 'desc' : 'asc')->paginate(12)->withQueryString(),
            'members' => $workspace->members()->select('users.id', 'name')->get(),
            'filters' => $request->only(['status', 'source', 'assigned_to_id', 'from', 'to', 'q']),
        ]);
    }

    public function store(StoreLeadRequest $request, AILeadScoringService $scoring, ActivityLogger $logger): RedirectResponse
    {
        $workspace = $request->attributes->get('workspace');
        $lead = new Lead([...$request->validated(), 'workspace_id' => $workspace->id]);
        $score = $scoring->score($lead);
        $lead->fill(['ai_score' => $score['score'], 'ai_reason' => $score['reason']])->save();
        $logger->log($workspace, 'lead.created', "Lead {$lead->name} created.", $lead);
        User::find($lead->assigned_to_id)?->notify(new CrmAssignmentNotification('New lead assigned', $lead->name, route('leads.show', $lead)));

        return back()->with('success', 'Lead created.');
    }

    public function show(Request $request, Lead $lead): Response
    {
        $this->authorize('view', $lead);

        return Inertia::render('Leads/Show', [
            'lead' => $lead->load(['assignedTo:id,name', 'activities.user:id,name', 'notes.user:id,name']),
            'tasks' => $lead->tasks()->with('assignedTo:id,name')->get(),
            'emails' => $lead->emails()->latest()->get(),
            'files' => $lead->files()->latest()->get(),
        ]);
    }

    public function update(StoreLeadRequest $request, Lead $lead, AILeadScoringService $scoring, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('update', $lead);
        $lead->fill($request->validated());
        $score = $scoring->score($lead);
        $lead->fill(['ai_score' => $score['score'], 'ai_reason' => $score['reason']])->save();
        $logger->log($lead->workspace, 'lead.updated', "Lead {$lead->name} updated.", $lead);

        return back()->with('success', 'Lead updated.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);
        $lead->delete();

        return back()->with('success', 'Lead deleted.');
    }

    public function convert(Request $request, Lead $lead, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('update', $lead);

        DB::transaction(function () use ($lead, $logger) {
            Customer::create([
                'workspace_id' => $lead->workspace_id,
                'owner_id' => $lead->assigned_to_id,
                'name' => $lead->name,
                'company_name' => $lead->company,
                'email' => $lead->email,
                'phone' => $lead->phone,
            ]);
            $lead->update(['status' => 'converted']);
            $logger->log($lead->workspace, 'lead.converted', "Lead {$lead->name} converted to customer.", $lead);
        });

        return back()->with('success', 'Lead converted.');
    }

    public function export(Request $request): StreamedResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($request->user()->canInWorkspace('manage_leads', $workspace), 403);

        return response()->streamDownload(function () use ($workspace) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'company', 'email', 'phone', 'status', 'source', 'value', 'ai_score']);
            $workspace->leads()->orderBy('name')->chunk(100, function ($leads) use ($handle) {
                foreach ($leads as $lead) {
                    fputcsv($handle, [$lead->name, $lead->company, $lead->email, $lead->phone, $lead->status, $lead->source, $lead->value, $lead->ai_score]);
                }
            });
            fclose($handle);
        }, 'leadflow-leads.csv');
    }

    public function import(Request $request, AILeadScoringService $scoring, ActivityLogger $logger): RedirectResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($request->user()->canInWorkspace('manage_leads', $workspace), 403);
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $handle = fopen($validated['file']->getRealPath(), 'r');
        $headers = array_map('strtolower', fgetcsv($handle) ?: []);
        $created = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, array_pad($row, count($headers), null));
            if (empty($data['name'])) {
                continue;
            }
            $lead = new Lead([
                'workspace_id' => $workspace->id,
                'name' => $data['name'],
                'company' => $data['company'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? 'new',
                'source' => $data['source'] ?? 'website',
                'value' => $data['value'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);
            $score = $scoring->score($lead);
            $lead->fill(['ai_score' => $score['score'], 'ai_reason' => $score['reason']])->save();
            $created++;
        }
        fclose($handle);
        $logger->log($workspace, 'leads.imported', "{$created} leads imported from CSV.");

        return back()->with('success', "{$created} leads imported.");
    }
}
