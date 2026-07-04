<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($request->user()->canInWorkspace('view_reports', $workspace), 403);

        return Inertia::render('Reports/Index', [
            'winLoss' => [
                'won' => $workspace->deals()->where('stage', 'won')->count(),
                'lost' => $workspace->deals()->where('stage', 'lost')->count(),
            ],
            'salesByUser' => $workspace->deals()->selectRaw('owner_id, sum(value) as revenue, count(*) as deals')->with('owner:id,name')->groupBy('owner_id')->get(),
            'taskStats' => $workspace->tasks()->selectRaw('status, count(*) as count')->groupBy('status')->get(),
            'monthlyRevenue' => $workspace->deals()->where('stage', 'won')->get()->groupBy(fn ($deal) => Carbon::parse($deal->created_at)->format('M Y'))->map(fn ($deals, $month) => ['month' => $month, 'revenue' => (float) $deals->sum('value')])->values(),
            'leadSources' => $workspace->leads()->selectRaw('source, count(*) as count')->groupBy('source')->get(),
        ]);
    }
}
