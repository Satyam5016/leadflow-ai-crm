<?php

namespace App\Http\Controllers;

use App\Services\AI\AISalesAssistantService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AIAssistantController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AI/Assistant');
    }

    public function ask(Request $request, AISalesAssistantService $assistant): Response
    {
        $validated = $request->validate(['question' => ['required', 'string', 'max:500']]);
        $workspace = $request->attributes->get('workspace');

        return Inertia::render('AI/Assistant', [
            'answer' => $assistant->answer($workspace, $validated['question']),
            'question' => $validated['question'],
        ]);
    }
}
