<?php

namespace App\Domain\ThreatMonitoring\DTO;

use App\Domain\ThreatMonitoring\Enums\ThreatConfidence;
use App\Domain\ThreatMonitoring\Enums\ThreatSeverity;

class ThreatAssessmentResult
{
    /**
     * @param array<int, array{name: string, description: string, points: int, details?: array<string, mixed>}> $findings
     */
    public function __construct(
        public readonly ThreatSeverity $severity,
        public readonly ThreatConfidence $confidence,
        public readonly int $score,
        public readonly array $findings,
    ) {
    }
}
