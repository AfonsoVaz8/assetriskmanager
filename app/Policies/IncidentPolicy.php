<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncidentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }

    public function view(User $user, Incident $incident): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }

    public function update(User $user, Incident $incident): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }
}
