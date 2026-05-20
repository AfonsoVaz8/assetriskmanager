<?php

namespace App\Domain\IncidentManagement\Enums;

enum IncidentStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';

    public function isActive(): bool
    {
        return in_array($this, [self::OPEN, self::IN_PROGRESS], true);
    }
}
