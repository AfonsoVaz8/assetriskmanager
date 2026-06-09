<?php

namespace App\Enums;

enum DiscoveredHostStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case FILTERED = 'filtered';
    case UNKNOWN = 'unknown';
    case ERROR = 'error';
}
