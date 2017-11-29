<?php

namespace RicardoFontanelli\LaravelTelegram\Test\Unit;

use PHPUnit_Framework_TestCase;
use RicardoFontanelli\LaravelTelegram\Telegram;
use phpmock\phpunit\PHPMock;

class TelegramTest extends PHPUnit_Framework_TestCase
{
    use PHPMock;

    protected function setBuiltInFunction($function, $return)
    {
        $exec = $this->getFunctionMock('RicardoFontanelli\LaravelTelegram', $function);
        $exec->expects($this->any())->willReturn($return);
    }


    public function test_get_me_success()
    {
        $this->setBuiltInFunction('curl_exec', '{"ok": true}');
        $this->setBuiltInFunction('curl_getinfo', 200);

        $telegram = new Telegram('my-token', 'bot-name');
        $this->assertTrue($telegram->getMe()->getResult()['ok']);
        $this->assertEquals(200, $telegram->getMe()->getStatusCode());
    }

    public function test_get_me_fail()
    {
        $this->setBuiltInFunction('curl_exec', '{"ok": false}');
        $this->setBuiltInFunction('curl_getinfo', 401);

        $telegram = new Telegram('my-token', 'bot-name');
        $this->assertFalse($telegram->getMe()->getResult()['ok']);
        $this->assertEquals(401, $telegram->getMe()->getStatusCode());
    }
}
