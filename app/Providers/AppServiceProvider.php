<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (env('APP_ENV') !== 'local') {
            $this->app['request']->server->set('HTTPS', 'on');
            URL::forceScheme('https');
        }

        Gate::policy('App\Models\User', 'App\Policies\UserPolicy');
        Gate::policy('App\Models\InstitutionPerson', 'App\Policies\InstitutionPersonPolicy');
        // Gate::policy('App\Models\Institution', 'App\Policies\InstitutionPolicy');
    }
}
