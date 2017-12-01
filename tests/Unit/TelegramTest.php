<?php

namespace RicardoFontanelli\LaravelTelegram\Test\Unit;

use PHPUnit_Framework_TestCase;
use RicardoFontanelli\LaravelTelegram\Telegram;
use phpmock\phpunit\PHPMock;
use Exception;

class TelegramTest extends PHPUnit_Framework_TestCase
{
    use PHPMock;

    protected function getDefaultMessageResponse($chat_id = -88899999)
    {
        return [
            'ok'        => 1,
            'result'    => [
                'message_id' => 444,
                'from' => [
                    'id'        => 99999999,
                    'is_bot'    => 1,
                    'first_name' => 'Bot Fist Name',
                    'username'  => 'bot-name',
                ],
                'chat' => [
                    'id'    => $chat_id ,
                    'title' => 'Grupo Name',
                    'type'  => 'group',
                    'all_members_are_administrators' => 1
                ],
                'date' => 1512149396,
                'text' => 'message test',
            ]
        ];
    }

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

    public function test_get_content()
    {
        $this->setBuiltInFunction('curl_exec', '{"ok": false}');
        $this->setBuiltInFunction('curl_getinfo', 200);

        $telegram   = new Telegram('my-token', 'bot-name');
        $result     = $telegram->getMe()->getResult();
        
        $this->assertEquals($result, $telegram->getContent());
        $this->assertEquals($result, $telegram->getResponse());
    }

    public function test_has_error_function()
    {
        $this->setBuiltInFunction('curl_exec', '{"ok": true}');
        $this->setBuiltInFunction('curl_getinfo', 400);

        $telegram   = new Telegram('my-token', 'bot-name');
        $status     = $telegram->hasError();
        
        $this->assertTrue($status);
    }

    public function test_send_async_message()
    {
        $this->setBuiltInFunction('curl_exec', '{"ok": true}');
        $this->setBuiltInFunction('curl_getinfo', 200);

        $telegram   = new Telegram('my-token', 'bot-name');
        $result     = $telegram->async()->sendMessage('default', 'message test')->getContent();
        $status     = $telegram->hasError();

        $this->assertFalse($status);
        $this->assertEmpty($result);
    }

    public function test_send_message()
    {
        $api_response = $this->getDefaultMessageResponse();

        $this->setBuiltInFunction('curl_exec', json_encode($api_response));
        $this->setBuiltInFunction('curl_getinfo', 200);

        $telegram   = new Telegram('my-token', 'bot-name');
        $result     = $telegram->sendMessage('-88899999', $api_response['result']['text'])->getContent();
        $status     = $telegram->hasError();

        $this->assertFalse($status);
        $this->assertEquals($result, $api_response);
    }

    public function test_send_message_using_chat_list_alias()
    {
        $api_response = $this->getDefaultMessageResponse(9999999999);

        $this->setBuiltInFunction('curl_exec', json_encode($api_response));
        $this->setBuiltInFunction('curl_getinfo', 200);

        $telegram   = new Telegram('my-token', 'bot-name');
        $telegram->setChatList(['default' => 9999999999, 'error' => 888888888]);
        $result     = $telegram->sendMessage('default', $api_response['result']['text'])->getContent();
        $status     = $telegram->hasError();

        $this->assertFalse($status);
        $this->assertEquals($result, $api_response);
    }

    public function test_get_updates()
    {
        $api_response = ['ok' => true, 'result' => []];

        $this->setBuiltInFunction('curl_exec', json_encode($api_response));
        $this->setBuiltInFunction('curl_getinfo', 200);

        $telegram   = new Telegram('my-token', 'bot-name');
        $result     = $telegram->getUpdates(1, 2, 20)->getContent();

        $this->assertEquals($result, $api_response);
    }

    public function test_get_updates_throw_exception()
    {
        $api_response = ['ok' => true, 'result' => []];

        $exec = $this->getFunctionMock('RicardoFontanelli\LaravelTelegram', 'curl_exec');
        $exec->expects($this->any())->willThrowException(new Exception('exception-error-message'));
        $this->setBuiltInFunction('curl_getinfo', 500);

        $telegram   = new Telegram('my-token', 'bot-name');
        $result     = $telegram->getUpdates()->getContent();
        $status     = $telegram->hasError();

        $this->assertTrue($status);
        $this->assertEquals('exception-error-message - https://api.telegram.org/botmy-token/getUpdates', $result);
    }

    public function test_magic_method_endpoint_call()
    { 
        $api_response = ['ok' => true, 'result' => []];

        $this->setBuiltInFunction('curl_exec', json_encode($api_response));
        $this->setBuiltInFunction('curl_getinfo', 200);

        $telegram   = new Telegram('my-token', 'bot-name');
        $result     = $telegram->getChat(['method' => 'GET', 'chat_id' => -8888999])->getContent();

        $this->assertEquals($result, $api_response);
    }
}
