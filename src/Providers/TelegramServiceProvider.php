<?php

namespace LaravelTelegram\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use LaravelTelegram\Telegram;

class TelegramServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    protected $instance;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('telegram', function ($app) {
            return new Telegram(
                Config::get('telegram.token', null),
                Config::get('telegram.botusername', null)
            );
        });
        $this->app->bind(Telegram::class, 'telegram');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['telegram'];
    }
}
