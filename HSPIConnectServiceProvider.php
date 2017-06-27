<?php

namespace Adietz\HSPIConnect;

use Illuminate\Support\ServiceProvider;

class HSPIConnectServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('iconnect', function()
        {
           return new \Adietz\HSPIConnect\HSPIConnect;
        });
    }
}
