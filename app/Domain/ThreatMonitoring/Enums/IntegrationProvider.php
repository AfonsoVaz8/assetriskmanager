<?php

namespace App\Domain\ThreatMonitoring\Enums;

enum IntegrationProvider: string
{
    case MICROSOFT_GRAPH = 'microsoft_graph';
    case SHODAN = 'shodan';
    case GENERIC_IP_INTELLIGENCE = 'generic_ip_intelligence';
}
