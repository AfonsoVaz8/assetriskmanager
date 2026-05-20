<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AttackSurfaceScope;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttackSurfaceScopePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }

    public function view(User $user, AttackSurfaceScope $attackSurfaceScope): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }

    public function update(User $user, AttackSurfaceScope $attackSurfaceScope): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }

    public function delete(User $user, AttackSurfaceScope $attackSurfaceScope): bool
    {
        return $user->role === UserRole::SECURITY_OFFICER;
    }
}
