<?php

namespace RicardoFontanelli\LaravelTelegram;

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
    private $endpoint = 'https://api.telegram.org/';

    /**
     * @var array a list of resources that are supported by this class, you can explore others resources using the __call method
    */
    private $resources = [
        'sendMessage',
        'getUpdates',
        'getMe',
    ];

    private $botName;

    private $token;

    /**
     * @var array a list of configured chat room list 'name' => telegram_chat_id
     */
    private $chatList = [];

    /**
     * @var resource curl instance
     */
    private $client;

    /**
     * @var array HTTP body response converted to array
     */
    private $result;

    private $httpStatus;

    /**
     * @var bool Define if the request will be async
     */
    private $async = false;

    /**
     * @var bool true or false if the request return an error
     */
    private $hasError;

    /**
     * Create a telegram Http Client.
     *
     * @param string $token   the bot Telegram token more in: https://core.telegram.org/bots
     * @param string $botName the bot username
     *
     * @return object $this
     */
    public function __construct($token, $botName)
    {
        isset($token) ? $this->token = $token : null;
        isset($botName) ? $this->botName = $botName : null;
        $this->resetConf();

        return $this;
    }
    
    /**
     * Define a list of chat room that the bot can send messages.
     *
     * @param array $list  a list of chat room ids that the Bot can send messages (name => telegram_group_id)
     *
     * @return object $this
     */ 
    public function setChatList(array $list)
    {
        $this->chatList = $list;
        return $this;
    }
    
    public function getResult()
    {
        return $this->result;
    }

    public function getContent()
    {
        return $this->getResult();
    }

    public function getResponse()
    {
        return $this->getResult();
    }

    public function getStatusCode()
    {
        return $this->httpStatus;
    }

    /**
     * Check has error in the last API call.
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
     * @return object $this
     */
    public function getMe()
    {
        return $this->callAPI('GET', 'getMe', []);
    }

    /**
     * Send a message to a specific chat in Telegram.
     *
     * @param string $chatId    the key value of the chat config list. If you provide a concrete chat id (that isn't a key value of the config file), it will be used 
     * @param string $text      a message with maximun lenght of 406 characters
     * @param string $parseMode HTML or Markdown Telegram will parse characteres
     *
     * @return object $this
     */
    public function sendMessage($chatId, $text, $parseMode = 'HTML')
    {
        $params = [];

        if (isset($chatId) && isset($text)) {
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
                    'parse_mode'=> $parseMode,
            ];
            return $this->callAPI('POST', 'sendMessage', $params);
        }
        $this->result = ['ok' => false, 'result' => 'Invalid params'];

        return $this;
    }

    /**
     * Receive incoming updates
     *
     * @param int $offset dentifier of the first update to be returned
     * @param int $limit  Limits the number of updates to be retrieved.
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
     * Receive incoming updates
     *
     * @param int $name         the name of the method called
     * @param int $arguments    an array with parameters to be send
     *
     * @return object $this
     */
    public static function __callStatic($name, $arguments)
    {
        (!isset($this->resources[$name])) ? $this->resources[] = $name : null;

        $method = isset($arguments['method']) ? $arguments['method'] : 'POST';
        unset($arguments['method']);
        $params = json_encode($arguments);
        return $this->callAPI('POST', $name, json_encode($arguments));
    }

    /**
     * Receive incoming updates
     *
     * @param int $name         the name of the method called
     * @param int $arguments    an array with parameters to be send
     *
     * @return object $this
     */
    public function __call($name, $arguments)
    {
        $this->resources[] = $name;
        $method = isset($arguments['method']) ? $arguments['method'] : 'POST';
        unset($arguments['method']);
        $params = json_encode($arguments);
        return $this->callAPI('POST', $name, json_encode($arguments));
    }

    /**
     * Generate the resource URL based on the transaction type.
     *
     * @param string $name resouce name, based on $resources
     *
     * @throws Exception if the resource name is invalid
     */
    private function generateResourceUrl($name)
    {
        if (in_array($name, $this->resources)) {
            return $this->endpoint . 'bot'. $this->token . '/' . $name;
        }
        throw new \Exception('Invalid Telegram resource name' . $name, 1);
    }

    /** Reset the class property structure for a next http call */
    private function resetConf()
    {
        $this->client = null;
        $this->result = null;
        $this->httpStatus = 500;
        $this->hasError = true;
    }

    /** Setup default configuration for the curl client */
    private function setupClient()
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
     * Send a HTTP request to que API endpoint, it will register an erro (hasError) if the remote server return HTTP error (400,500,...) or 200 with json error structure array('ok'=>false).
     *
     * @param string $method       HTTP method (POST, GET ...)
     * @param string $resourceName resouce name, based on $resources
     * @param array  $data         an array of data to be converted to JSON and sent to the API
     *
     * @return $this
     */
    private function callAPI($method, $resourceName, $data)
    {
        $this->setupClient();
        $url = $this->generateResourceUrl($resourceName);
        $dataString = json_encode($data);

        if ($method == 'POST') {
            curl_setopt($this->client, CURLOPT_CUSTOMREQUEST, 'POST');
            if ($data) {
                curl_setopt($this->client, CURLOPT_POSTFIELDS, $dataString);
                curl_setopt($this->client, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: '.strlen($dataString)]);
            }
        }

        curl_setopt($this->client, CURLOPT_URL, $url);

        try {
            $this->result = curl_exec($this->client);
            $this->httpStatus = curl_getinfo($this->client, CURLINFO_HTTP_CODE);
            curl_close($this->client);

            if (!$this->async) {
                if ($this->httpStatus == '200') {
                    $this->result = json_decode($this->result, true);
                    if (is_array($this->result)) {
                        if (array_key_exists('ok', $this->result)) {
                            if ($this->result['ok'] == true) {
                                $this->hasError = false;
                            }
                        }
                    }
                }
            } else {
                $this->hasError = false;
                $this->result = [];
            }
        } catch (\Exception $e) {
            $this->httpStatus = '500';
            $this->result = $e->getMessage().' - '.$url;
        }

        return $this;
    }
}
