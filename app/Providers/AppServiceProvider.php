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
    }
}
