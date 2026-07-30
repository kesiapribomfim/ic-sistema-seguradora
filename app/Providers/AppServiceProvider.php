<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Apolice;
use App\Observers\ApoliceObserver;

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
        Apolice::observe(ApoliceObserver::class);
    }
}
