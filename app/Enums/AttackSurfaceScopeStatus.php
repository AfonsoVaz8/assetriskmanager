<?php

namespace App\Enums;

enum AttackSurfaceScopeStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case DISABLED = 'disabled';
}
