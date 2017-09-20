# LaravelTelegram
A simple and lightweight Laravel 4.2 and Laravel 5.* wrapper to interact with Telegram Bot.

## Get Started:
* First of all, you should create a Telegram Bot, you can use [Bot Father](https://core.telegram.org/bots#6-botfather) to do that;
* Create a Telegram chat room (group) and add the Bot to this group, now the Bot can send messages!
* After publish the package, open the telegram config file and provide all the required information.

## Installation

1) The Laravel Telegram Service Provider can be installed via ...
```sh 
composer require ricardofontanelli/laravel-telegram:1.0 
```
 or [Composer](http://getcomposer.org) by requiring the `ricardofontanelli/laravel-telegram` package in your project's `composer.json`
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

2) To use the Laravel Telegram Service Provider, you must register the provider when bootstrapping your application. If you are using Laravel >= 5.5, the package 
supports Automatic Package Discovery, skip to the step 3.

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

3) After that, run the command above to publish the Telegram config file, you must provide your Telegram Bot credentials and chat room information. 
## Publishing the package
Now, you should publish the package to generate the config file, after that, edit the config file with your Telegram Bot credentials.
### Laravel 4.2
The config file will be generate here: ```app/config/packages/ricardofontanelli/laravel-telegram/config.php```
```php 
php artisan config:publish ricardofontanelli/laravel-telegram
```
### Laravel 5.*
The config file will be generate here: ```app/config/telegram.php```
```php 
php artisan vendor:publish --provider="RicardoFontanelli\LaravelTelegram\TelegramServiceProvider"
```

### Send a message:
Now you can use it by ```php artisan tinker``` and run: 

```php
// Send a message
Telegram::sendMessage('default', 'Here we go!');
// or async
Telegram::async()->sendMessage('default', 'Here we go!');
```
The first value is the config key name of the chat/group id where the Bot will publicate the message, you can provide the chat/group id directly. 
### Get information about the Bot:
```php
// Send an async message
Telegram::getMe()->getResult();
```

### Get Bot update:
```php
// Send an async message
Telegram::getUpdates()->getResult();
```

### Other methods:
The class has use a magic method to call unsuported methods:
```php
$params = ['method'=>'GET'];
Telegram::getWebhookInfo($params)->getResult()
```
You can use the variable ```$params``` to send query parameters and define the HTTP method (according the Telegram Bot Api documentation).

## Find more:
You can find more here (Telegram Bot API)[https://core.telegram.org/bots/api], but if you need to call a method that the class doesn't support, feel free to send a PR.