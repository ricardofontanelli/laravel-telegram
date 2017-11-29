<?php

namespace RicardoFontanelli\LaravelTelegram\Test\Feature;

use PHPUnit_Framework_TestCase;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Container\Container;

use RicardoFontanelli\LaravelTelegram\TelegramFacade as Telegram;
use RicardoFontanelli\LaravelTelegram\TelegramServiceProvider;

class LaravelTelegramServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (! class_exists(Application::class)) {
            $this->markTestSkipped();
        }
        parent::setUp();
    }

    protected function setupApplication()
    {
        // Create the application such that the config is loaded.
        $app = new Application();
        $app->setBasePath(sys_get_temp_dir());
        $app->instance('config', new Repository());
        return $app;
    }

    /**
     * @param Container $app
     *
     * @return TelegramServiceProvider
     */
    private function setupServiceProvider(Container $app)
    {
        // Create and register the provider.
        $provider = new TelegramServiceProvider($app);
        $app->register($provider);
        $provider->boot();
        return $provider;
    }

    public function test_facade_can_be_resolved_to_service_instance()
    {
        $app = $this->setupApplication();
        $this->setupServiceProvider($app);
        // Mount facades
        Telegram::setFacadeApplication($app);
        // Get an instance of a telegram class via the facade.
        $instance = Telegram::getMe();
        $this->assertInstanceOf('RicardoFontanelli\LaravelTelegram\Telegram', $instance);
    }
    
    public function test_service_name_is_provided()
    {
        $app = $this->setupApplication();
        $provider = $this->setupServiceProvider($app);
        $this->assertContains('telegram', $provider->provides());
    }
}
