<?php

namespace App\Domain\ThreatMonitoring\Enums;

enum ThreatSeverity: string
{
    case INFORMATIONAL = 'informational';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
