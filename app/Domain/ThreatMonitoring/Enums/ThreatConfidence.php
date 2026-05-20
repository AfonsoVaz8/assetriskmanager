<?php

namespace App\Domain\ThreatMonitoring\Enums;

enum ThreatConfidence: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
