<?php

namespace Roykarte\LaravelSendinBlue;

use Illuminate\Support\ServiceProvider;

class SendinBlueServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['swift.transport']->extend('sendinblue', function ($app) {
            return new SendinBlueTransport;
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
