<?php

namespace App\Providers;

use App\Models\Archive;
use App\Models\Cabinet;
use App\Observers\CabinetObserver;
use App\Policies\ArchivePolicy;
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
        Gate::policy(Archive::class, ArchivePolicy::class);
        Cabinet::observe(CabinetObserver::class);
    }
}
