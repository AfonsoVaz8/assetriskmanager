<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IntegrationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Integration $integration): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }

    public function update(User $user, Integration $integration): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }

    public function delete(User $user, Integration $integration): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }
}
