# LaravelTelegram
A simple and lightweight Laravel 4 wrapper to interact with Telegram Bot.

## Get Started:
* First of all, you should create a Telegram Bot, you can use [Bot Father](https://core.telegram.org/bots#6-botfather) to do that;
* Create a Telegram chat room (group) and add the Bot to this group, now the Bot can send messages!
* After publish the package, open the ```config/packages/ricardofontanelli/laravel-telegram/config.php``` and provide all the needed information.

## Installation
The Laravel Telegram Service Provider can be installed via [Composer](http://getcomposer.org) by requiring the `ricardofontanelli/laravel-telegram` package in your project's `composer.json`.

```json
{
    "require": {
        "ricardofontanelli/laravel-telegram": "1.0"
    }
}
```

Then run a composer update
```sh
php composer update
```

To use the Laravel Telegram Service Provider, you must register the provider when bootstrapping your application.

In Laravel find the `providers` key in your `config/app.php` and register the Laravel Telegram Service Provider.

```php
    'providers' => array(
        // ...
        'RicardoFontanelli\LaravelTelegram\TelegramServiceProvider',
    )
```

Find the `aliases` key in your `config/app.php` and add the Laravel Telegram facade alias.

```php
    'aliases' => array(
        // ...
        'Telegram' => 'RicardoFontanelli\LaravelTelegram\TelegramFacade',
    )
```

After that, run ```php artisan config:publish ricardofontanelli/laravel-telegram```, to publish the Telegram config file, you must to provide you Telegram Bot credentials and chat room information.

Now you can use it by ```php artisan tinker``` and run: 

```php 
Telegram::async()->sendMessage('default', 'Test message');
```