<?php

namespace App\Providers;

use App\Models\Integration;
use App\Models\Incident;
use App\Models\User;
use App\Models\AttackSurfaceScope;
use App\Policies\AttackSurfaceScopePolicy;
use App\Policies\IntegrationPolicy;
use App\Policies\IncidentPolicy;
use Hash;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Integration::class => IntegrationPolicy::class,
        Incident::class => IncidentPolicy::class,
        AttackSurfaceScope::class => AttackSurfaceScopePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        if (config("ldap.enabled")) {
            Fortify::authenticateUsing(function ($request) {
                $validated = Auth::validate([
                    'userPrincipalName' => $request->email,
                    'password' => $request->password,
                    // In case LDAP server is down, uses user cached password
                    'fallback' => [
                        'email' => $request->email,
                        'password' => $request->password
                    ],
                ]);
                return $validated ? Auth::getLastAttempted() : null;
            });
        }
        else {
            Fortify::authenticateUsing(function ($request) {
                $user = User::where('email', $request->email)->first();
                $validated = $user && Hash::check($request->password, $user->password);
                return $validated ? $user : null;
            });
        }

    }
}
