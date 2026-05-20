<?php

namespace App\Enums;

enum DiscoveredHostStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ERROR = 'error';
}
