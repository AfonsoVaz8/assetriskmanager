<?php

namespace App\Enums;

enum AttackSurfaceScopeType: string
{
    case REGISTERED_ASSETS = 'registered_assets';
    case CIDR_RANGE = 'cidr_range';
    case HOSTNAME_TARGET = 'hostname_target';
    case DOMAIN_EXPANSION = 'domain_expansion';
}
