<?php

namespace App\Providers;

use App\Contracts\Services\PromotionServiceInterface;
use App\Contracts\Services\SeparationServiceInterface;
use App\Contracts\Services\StaffManagementServiceInterface;
use App\Contracts\Services\TransferServiceInterface;
use App\Services\Staff\PromotionService;
use App\Services\Staff\SeparationService;
use App\Services\Staff\StaffManagementService;
use App\Services\Staff\TransferService;
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
        // Register service layer bindings
        $this->app->bind(StaffManagementServiceInterface::class, StaffManagementService::class);
        $this->app->bind(PromotionServiceInterface::class, PromotionService::class);
        $this->app->bind(TransferServiceInterface::class, TransferService::class);
        $this->app->bind(SeparationServiceInterface::class, SeparationService::class);
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

        \App\Models\Qualification::observe(\App\Observers\QualificationObserver::class);
    }
}
