<?php

namespace RicardoFontanelli\LaravelTelegram;

use Exception;

/**
 * Service class to comunicate with Telegram API.
 *
 * @author Ricardo Fontanelli <ricardo.fontanelli@hotmail.com>
 */
class Telegram
{
    /**
     * @var string an array with the main url and resources endpoint see provided from \Config::get('app.api_ftd')
    */
    protected $endpoint = 'https://api.telegram.org/';

    /**
     * @var array a list of resources that are supported by this class, you can explore others resources using the __call method
    */
    protected $resources = [
        'sendMessage',
        'getUpdates',
        'getMe',
    ];

    /**
     * @var string telegram bot username
     */
    protected $botName;

    /**
     * @var string telegram bot access token
     */
    protected $token;

    /**
     * @var array a list of configured chat room list 'name' => telegram_chat_id
     */
    protected $chatList = [];

    /**
     * @var resource curl instance
     */
    protected $client;

    /**
     * @var array HTTP body response parsed to array
     */
    protected $result;

    /**
     * @var int HTTP Status Code
     */
    protected $httpStatus;

    /**
     * @var bool Define if the request will be async
     */
    protected $async = false;

    /**
     * @var bool true or false if the request returns an error
     */
    protected $hasError;

    /**
     * Creates a telegram Http Client.
     *
     * @param string $token   the bot Telegram token more in: https://core.telegram.org/bots
     * @param string $botName the bot username
     *
     * @return object $this
     */
    public function __construct($token = null, $botName = null)
    {
        $this->token    = $token;
        $this->botName  = $botName;
        $this->resetConf();
        return $this;
    }
    
    /**
     * Defines a list of chat rooms that the bot can interact (send messages).
     *
     * @param array $list  a list of chat rooms ids that the Bot can send messages (name => telegram_group_id)
     *
     * @return object $this
     */
    public function setChatList(array $list)
    {
        $this->chatList = $list;
        return $this;
    }
  
    /**
     * Returns the last http response from telegram API
     *
     * @return array|bol the last response from telegram
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Alias to getResult()
     */
    public function getContent()
    {
        return $this->getResult();
    }

    /**
     * Alias to getResult()
     */
    public function getResponse()
    {
        return $this->getResult();
    }

    /**
     * Returns the lsta HTTP Status code from Telegram API
     */
    public function getStatusCode()
    {
        return $this->httpStatus;
    }

    /**
     * Checks if has error in the last API call.
     *
     * @return bool
     */
    public function hasError()
    {
        return $this->hasError;
    }

    /**
     * Define if the call should be sync or async.
     *
     * @param bool $status change the behavior of api call
     *
     * @return object $this
     */
    public function async($status = true)
    {
        $this->async = $status;

        return $this;
    }

    /**
     * A simple method for testing your bot's auth token. Requires no parameters. Returns basic information about the bot
     *
     * @param array $additional_params Additional parameters to send to the API
     * @return object $this
     */
    public function getMe()
    {
        return $this->callAPI('GET', 'getMe', []);
    }

    /**
     * Sends a message to a specific chat in Telegram.
     *
     * @param string $chatId    the key value of the chat config list. If you provide a concrete chat id (that isn't a key value of the config file), it will be used
     * @param string $text      a message with maximun lenght of 406 characters
     * @param string $parseMode HTML or Markdown Telegram will parse characteres
     * @param array  $additionalParams Additional parameters to send to the API
     *
     * @return object $this
     */
    public function sendMessage($chatId, $text, $parseMode = 'HTML', $additionalParams = [])
    {
        $params = [];
        
        // Define the chat id, by config or concrete value
        if (isset($this->chatList[$chatId])) {
            $chatId = $this->chatList[$chatId];
        }
        
        $stringLenUtf8 = mb_strlen($text, 'UTF-8');

        if ($stringLenUtf8 > 4096) {
            $text = mb_substr($text, 0, 4096);
        }

        $params = [
                'chat_id'   => $chatId,
                'text'      => $text,
        ];

        if (is_array($parseMode)) {
            $params = array_merge($params, $parseMode);
        } else {
            $params['parse_mode'] = $parseMode;
            $params = array_merge($params, $additionalParams);
        }

        return $this->callAPI('POST', 'sendMessage', $params);
        
        return $this;
    }

    /**
     * Receive incoming updates
     *
     * @param int $offset  Identifier of the first update to be returned
     * @param int $limit   Limits the number of updates to be retrieved.
     * @param int $timeout Timeout in seconds for long polling.
     *
     * @return object $this
     */
    public function getUpdates($offset = 0, $limit = 100, $timeout = 0)
    {
        $params = [];
        
        if (isset($offset)) {
            $params['offset'] = $offset;
        }

        if (isset($limit)) {
            $params['limit'] = $limit;
        }

        $params['timeout'] = $timeout;

        return $this->callAPI('POST', 'getUpdates', $params);
    }

    /**
     * Static implementation of __call, for more details, check __call documentation
     *
     * @param int $name
     * @param int $arguments
     *
     * @return object $this
     */
    public static function __callStatic($name, $arguments)
    {
        (!isset($this->resources[$name])) ? $this->resources[] = $name : null;

        if (count($arguments) == 1 && is_array($arguments[0])) {
            $arguments = $arguments[0];
        }

        $method = isset($arguments['method']) ? $arguments['method'] : 'POST';
        unset($arguments['method']);
        return $this->callAPI('POST', $name, $arguments);
    }

    /**
     * Execute a custom query to the API
     *
     * @param int $name         the name of the method to be called
     * @param int $arguments    an array with parameters to be send, if the HTTP mthod is GET, don't forget to define a param called $arguments['method'= 'GET',
     * the default method is POST
     *
     * @return object $this
     */
    public function __call($name, $arguments)
    {
        $this->resources[] = $name;

        if (count($arguments) == 1 && is_array($arguments[0])) {
            $arguments = $arguments[0];
        }

        $method = isset($arguments['method']) ? $arguments['method'] : 'POST';
        unset($arguments['method']);
        return $this->callAPI('POST', $name, $arguments);
    }

    /**
     * Generate the resource URL based on the transaction type.
     *
     * @param string $name resouce name, based on $resources
     *
     * @throws Exception if the resource name is invalid
     */
    protected function generateResourceUrl($name)
    {
        if (in_array($name, $this->resources)) {
            return $this->endpoint . 'bot'. $this->token . '/' . $name;
        }
        throw new Exception('Invalid Telegram resource name' . $name, 1);
    }

    /** Reset the class property structure for a next http call */
    protected function resetConf()
    {
        $this->client     = null;
        $this->result     = null;
        $this->httpStatus = 500;
        $this->hasError   = true;
    }

    /** Setup default configuration for the curl client */
    protected function setupClient()
    {
        $this->resetConf();

        $this->client = curl_init();
        
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->client, CURLOPT_ENCODING, 'gzip');
        curl_setopt($this->client, CURLOPT_VERBOSE, false);
        curl_setopt($this->client, CURLOPT_HEADER, false);
        curl_setopt($this->client, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        if ($this->async) {
            curl_setopt($this->client, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($this->client, CURLOPT_TIMEOUT, 1);
        }
    }

    /**
     * Exec the curl http request, an easy way to mock this step
     * @return void
    */
    protected function execHttpRequest()
    {
        $this->result       = curl_exec($this->client);
        $this->httpStatus   = curl_getinfo($this->client, CURLINFO_HTTP_CODE);
        curl_close($this->client);
    }

    /**
     * Send a HTTP request to que API endpoint, it will register an erro (hasError) if the remote server return HTTP error (400,500,...) or 200 with json error structure array('ok'=>false).
     *
     * @param string $method       HTTP method (POST, GET ...)
     * @param string $resourceName resouce name, based on $resources
     * @param array  $data         an array of data to be converted to JSON and sent to the API
     *
     * @return $this
     */
    protected function callAPI($method, $resourceName, $data)
    {
        $this->setupClient();

        $url        = $this->generateResourceUrl($resourceName);
        $dataString = json_encode($data);

        if ($method == 'POST') {
            curl_setopt($this->client, CURLOPT_CUSTOMREQUEST, 'POST');
            if (isset($data)) {
                curl_setopt($this->client, CURLOPT_POSTFIELDS, $dataString);
                curl_setopt($this->client, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: '.strlen($dataString)]);
            }
        }

        curl_setopt($this->client, CURLOPT_URL, $url);

        try {
            $this->execHttpRequest();

            if (! $this->async) {
                $this->result = json_decode($this->result, true);
                if (isset($this->result['ok'])) {
                    $this->hasError = ! $this->result['ok'];
                }
            } else {
                $this->hasError = false;
                $this->result   = [];
            }
        } catch (Exception $e) {
            $this->httpStatus   = 500;
            $this->result       = $e->getMessage() . ' - ' . $url;
        }

        return $this;
    }
}
