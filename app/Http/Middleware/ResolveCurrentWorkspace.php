<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $workspace = $user->currentWorkspace();

        if (! $workspace && ! $request->routeIs('workspaces.store')) {
            return redirect()->route('workspaces.create');
        }

        if ($workspace) {
            session(['workspace_id' => $workspace->id]);
            $request->attributes->set('workspace', $workspace);
        }

        return $next($request);
    }
}
