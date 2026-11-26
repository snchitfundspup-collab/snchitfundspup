<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /* the Usage dashboard is for Sathiya only */
        Gate::define('view-usage', fn (mixed $user): bool => $user instanceof User && $user->can_view_usage === true);
        Gate::define('edit-loans', fn (mixed $user): bool => $user instanceof User && $user->can_edit_loans === true);
        /* the owner sees all of Micro Finance; every other staff member only their own */
        Gate::define('finance-owner', fn (mixed $user): bool => $user instanceof User && $user->is_owner === true);
    }
}
