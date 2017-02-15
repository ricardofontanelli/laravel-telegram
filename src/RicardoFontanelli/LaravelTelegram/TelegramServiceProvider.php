<?php

namespace RicardoFontanelli\LaravelTelegram;

use Illuminate\Support\ServiceProvider;
use RicardoFontanelli\LaravelTelegram\Telegram;

class TelegramServiceProvider extends ServiceProvider
{

    /**
     * Abstract type to bind Sentry as in the Service Container.
     *
     * @var string
     */
    public static $abstract = 'telegram';
    
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

	$app = $this->app;
        
	// Laravel 4.x compatibility
        if (version_compare($app::VERSION, '5.0') < 0) {
            $this->package('ricardofontanelli/telegram', static::$abstract);
        } else {
            // the default configuration file
            $this->publishes([
                __DIR__ . '/../../config/config.php' => config_path(static::$abstract . '.php'),
            ], 'config');
        }
	}

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(static::$abstract . '.config', function ($app) {
            // sentry::config is Laravel 4.x
            $user_config = $app['config'][static::$abstract] ?: $app['config'][static::$abstract . '::config'];
            // Make sure we don't crash when we did not publish the config file
            if (is_null($user_config)) {
                $user_config = [];
            }
            return $user_config;
        });
        
        
        $this->app->bind('RicardoFontanelli\LaravelTelegram\Telegram', function ($app) {
            
            $user_config = $app[static::$abstract . '.config'];

            $token          = isset($user_config['token'])       ? $user_config['token']       : null;
            $botusername    = isset($user_config['botusername']) ? $user_config['botusername'] : null;
            $chats          = isset($user_config['chats'])       ? $user_config['chats']       : [];

            $client = new Telegram($token, $botusername);
            $client->setChatList($chats);
            
            return $client;
        });

        $this->app->singleton(static::$abstract, 'RicardoFontanelli\LaravelTelegram\Telegram');
        
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [static::$abstract];
    }
}
