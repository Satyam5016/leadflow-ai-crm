<?php

namespace App\Services\AI;

class AIEmailSummaryService
{
    public function summarize(string $body): string
    {
        return str($body)->squish()->limit(220)->toString();
    }
}
