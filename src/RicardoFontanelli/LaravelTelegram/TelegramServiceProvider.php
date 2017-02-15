<?php

namespace RicardoFontanelli\LaravelTelegram;

use Illuminate\Support\ServiceProvider;
use RicardoFontanelli\LaravelTelegram\Telegram;

class TelegramServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
    */
	public function boot()
	{
		$this->package('ricardofontanelli/telegram');
	}

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('RicardoFontanelli\LaravelTelegram\Telegram', function ($app) {
            
            $client = new Telegram($app['config']->get('telegram::token', null), $app['config']->get('telegram::botusername', null));
            $client->setChatList($app['config']->get('telegram::chats', []));
            
            return $client;
        });

        $this->app->singleton('telegram', 'RicardoFontanelli\LaravelTelegram\Telegram');
        
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
