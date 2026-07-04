<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');
        $totalLeads = $workspace->leads()->count();
        $converted = $workspace->leads()->where('status', 'converted')->count();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'leads' => $totalLeads,
                'customers' => $workspace->customers()->count(),
                'deals' => $workspace->deals()->count(),
                'revenueWon' => (float) $workspace->deals()->where('stage', 'won')->sum('value'),
                'conversionRate' => $totalLeads ? round(($converted / $totalLeads) * 100, 1) : 0,
                'pendingTasks' => $workspace->tasks()->where('status', '!=', 'completed')->count(),
            ],
            'pipeline' => $workspace->deals()->selectRaw('stage, count(*) as count, sum(value) as value')->groupBy('stage')->get(),
            'leadSources' => $workspace->leads()->selectRaw('source, count(*) as count')->groupBy('source')->get(),
            'monthlyRevenue' => $workspace->deals()->where('stage', 'won')->get()->groupBy(fn ($deal) => Carbon::parse($deal->created_at)->format('M Y'))->map(fn ($deals, $month) => ['month' => $month, 'revenue' => (float) $deals->sum('value')])->values(),
            'taskStats' => $workspace->tasks()->selectRaw('status, count(*) as count')->groupBy('status')->get(),
            'recentTasks' => $workspace->tasks()->with('assignedTo:id,name')->where('status', '!=', 'completed')->orderBy('due_date')->limit(6)->get(),
            'recentActivities' => ActivityLog::where('workspace_id', $workspace->id)->latest()->limit(8)->get(),
        ]);
    }
}
