<?php

namespace App\Providers;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        foreach (array_keys(PermissionService::GROUPS) as $group) {
            Gate::define($group, static fn (User $user): bool => PermissionService::allows($user, $group));
        }

        Gate::define('view-business-scope', static function (User $user, string $scope): bool {
            if ($user->isOwner()) {
                return true;
            }

            if ($user->isSystemAdmin()) {
                return PermissionService::hasActiveBusinessAccessGrant($user, $scope) !== null;
            }

            return false;
        });
    }
}
