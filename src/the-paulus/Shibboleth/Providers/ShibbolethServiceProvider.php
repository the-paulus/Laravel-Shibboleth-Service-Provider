<?php
namespace ThePaulus\Shibboleth\Providers;

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

        $publishes = [
            __DIR__ . '/../../../config/shibboleth.php'     => config_path('shibboleth.php'),
            __DIR__ . '/../Controllers/ShibbolethController.php'    => app_path('Http' . DIRECTORY_SEPARATOR . 'Controllers'),
        ];

        if(file_exists(app_path('Models'))) {

            if(
                file_exists(app_path('Models') . DIRECTORY_SEPARATOR . 'User.php') ||
                file_exists(app_path('Models') . DIRECTORY_SEPARATOR . 'UserGroup.php')
            ) {

                $publishes[__DIR__ . '/../Models/User.php'] = app_path('Models') . DIRECTORY_SEPARATOR . 'User.php';
                $publishes[__DIR__ . '/../Models/Group.php'] = app_path('Models') . DIRECTORY_SEPARATOR . 'UserGroup.php';

            }

        } else {

            if(
                file_exists(app_path() . DIRECTORY_SEPARATOR . 'User.php') ||
                file_exists(app_path() . DIRECTORY_SEPARATOR . 'UserGroup.php')
            ) {

                $publishes[__DIR__ . '/../Models/User.php'] = app_path() . DIRECTORY_SEPARATOR . 'User.php';
                $publishes[__DIR__ . '/../Models/Group.php'] = app_path() . DIRECTORY_SEPARATOR . 'UserGroup.php';

            }
        }



        $this->publishes($publishes);

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
