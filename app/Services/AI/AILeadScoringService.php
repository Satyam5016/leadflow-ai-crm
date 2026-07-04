<?php

namespace App\Services\AI;

use App\Models\Lead;

class AILeadScoringService
{
    public function score(Lead $lead): array
    {
        $score = 35;
        $reasons = [];

        if (in_array($lead->source, ['referral', 'website', 'LinkedIn'], true)) {
            $score += 20;
            $reasons[] = 'High-intent lead source.';
        }

        if ($lead->company) {
            $score += 15;
            $reasons[] = 'Company information is available.';
        }

        if ($lead->email && ! str_ends_with($lead->email, '@gmail.com')) {
            $score += 15;
            $reasons[] = 'Business email domain detected.';
        }

        if (str($lead->notes)->contains(['budget', 'demo', 'urgent', 'proposal'], true)) {
            $score += 15;
            $reasons[] = 'Notes mention buying intent.';
        }

        return [
            'score' => min(100, $score),
            'reason' => implode(' ', $reasons) ?: 'Starter score based on limited lead data.',
        ];
    }
}
