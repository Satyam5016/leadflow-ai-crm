<?php

namespace App\Services\AI;

use App\Models\Workspace;

class AISalesAssistantService
{
    public function answer(Workspace $workspace, string $question): string
    {
        $q = str($question)->lower();

        if ($q->contains('follow up')) {
            $leads = $workspace->leads()->whereIn('status', ['new', 'contacted'])->orderByDesc('ai_score')->limit(3)->pluck('name')->join(', ');

            return $leads ? "Follow up with {$leads}. They are open leads with the strongest current scores." : 'No urgent follow-ups found.';
        }

        if ($q->contains('negotiation')) {
            $count = $workspace->deals()->where('stage', 'negotiation')->count();

            return "There are {$count} deals in negotiation. Check low-probability deals first and add next-step tasks.";
        }

        if ($q->contains('customer')) {
            $customer = $workspace->customers()->latest()->first();

            return $customer ? "{$customer->name} from {$customer->company_name} has recent CRM history ready for review in the customer timeline." : 'No customers are available to summarize yet.';
        }

        return 'Mock AI response: connect an LLM API key later and keep this service contract unchanged.';
    }
}
