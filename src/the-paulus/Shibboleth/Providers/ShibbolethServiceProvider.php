<?php namespace ThePaulus\Shibboleth\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use ThePaulus\Shibboleth\Providers\ShibbolethUserProvider;

class ShibbolethServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot() {

        $this->loadRoutesFrom(__DIR__ . '/../../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../../resources/views', 'laravel-shibboleth');

        $this->publishes([
          __DIR__ . '/../../config/shibboleth.php'  => config_path('shibboleth.php'),
          __DIR__ . '/../../Models/User.php'        => base_path('/app/Models/User.php'),
          __DIR__ . '/../../Models/Group.php'       => base_path('/app/Models/Group.php'),
          __DIR__ . '/../../../resources/views'     => resource_path('views/vendor/courier'),
        ]);

        //$this->registerPolicies();

        Auth::provider('shibboleth', function($app, array $config) {

          return new ShibbolethUserProvider($app->make($this->app['config']['auth.providers.users.model']));

        });

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['auth']->extend('shibboleth', function ($app) {

            return new ShibbolethUserProvider($this->app['config']['auth.providers.users.model']);

        });


    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
