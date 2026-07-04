<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');
        $query = $workspace->customers()->with('owner:id,name');
        $query->when($request->filled('assigned_to_id'), fn ($q) => $q->where('owner_id', $request->integer('assigned_to_id')));
        $query->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')));
        $query->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')));
        $query->when($request->filled('q'), function ($q) use ($request) {
            $search = '%'.$request->string('q')->toString().'%';
            $q->where(fn ($inner) => $inner->where('name', 'like', $search)->orWhere('company_name', 'like', $search)->orWhere('email', 'like', $search));
        });
        $sort = in_array($request->string('sort')->toString(), ['name', 'company_name', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'created_at';

        return Inertia::render('Customers/Index', [
            'customers' => $query->orderBy($sort, $sort === 'created_at' ? 'desc' : 'asc')->paginate(12)->withQueryString(),
            'members' => $workspace->members()->select('users.id', 'name')->get(),
            'filters' => $request->only(['assigned_to_id', 'from', 'to', 'q']),
        ]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($request->user()->canInWorkspace('manage_customers', $workspace), 403);
        $customer = Customer::create(['workspace_id' => $workspace->id] + $this->validated($request));
        $logger->log($workspace, 'customer.created', "Customer {$customer->name} created.", $customer);

        return back()->with('success', 'Customer created.');
    }

    public function show(Customer $customer): Response
    {
        abort_unless(auth()->user()->workspaces()->whereKey($customer->workspace_id)->exists(), 403);

        return Inertia::render('Customers/Show', [
            'customer' => $customer->load(['owner:id,name', 'activities.user:id,name', 'notes.user:id,name']),
            'tasks' => $customer->tasks()->with('assignedTo:id,name')->get(),
            'emails' => $customer->emails()->latest()->get(),
            'files' => $customer->files()->latest()->get(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()->canInWorkspace('manage_customers', $customer->workspace), 403);
        $customer->update($this->validated($request));

        return back()->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        abort_unless(auth()->user()->canInWorkspace('manage_customers', $customer->workspace), 403);
        $customer->delete();

        return back()->with('success', 'Customer deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ]);
    }
}
