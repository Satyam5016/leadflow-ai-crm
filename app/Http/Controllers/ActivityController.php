<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');

        return Inertia::render('Activity/Index', [
            'activities' => ActivityLog::with('user:id,name')->where('workspace_id', $workspace->id)->latest()->paginate(25),
        ]);
    }
}
