<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Apolice;
use App\Observers\ApoliceObserver;
use App\Models\Segurado;
use App\Models\SeguradoPf;
use App\Models\SeguradoPj;
use App\Observers\SeguradoObserver;
use App\Observers\SeguradoPfObserver;
use App\Observers\SeguradoPjObserver;
use Illuminate\Support\Facades\Gate;

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
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
        Apolice::observe(ApoliceObserver::class);
        Segurado::observe(SeguradoObserver::class);
        SeguradoPf::observe(SeguradoPfObserver::class);
        SeguradoPj::observe(SeguradoPjObserver::class);
    }
}
