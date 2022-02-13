<?php

namespace Lib;

use Lib\CurlAPI;

/**
 * Telegram 自動通知機器人 API 連接類別
 */
class TelegramBotAPI extends CurlAPI
{
    protected $_host = 'https://api.telegram.org';
    protected $_baseUrl = "https://api.telegram.org";
    protected $_referer = 'https://api.telegram.org/';

    protected $_contentType = 'application/json;charset=UTF-8';
    protected $_userAgent = 'Chrome/98.0.4758.80';

    protected $_cUrl;

    protected static $_uniqueInstance = null;

    public static function getInstance()
    {
        if (self::$_uniqueInstance === null) self::$_uniqueInstance = new self;
        return self::$_uniqueInstance;
    }

    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * 發出 GET 請求
     *
     * @param  string       $url    網址
     * @param  string|null  $token  API 令牌；本類別不需要
     * @return array
     */
    public function get(string $url, ?string $token = null): array
    {
        $this->_reset();

        $url = (!preg_match('/^http/', $url)) ? "{$this->_baseUrl}/{$url}" : $url;

        curl_setopt($this->_cUrl, CURLOPT_URL, $url);
        curl_setopt($this->_cUrl, CURLOPT_HTTPGET, true);

        $response = $this->_exec();

        return [
            'httpStatusCode' => curl_getinfo($this->_cUrl, CURLINFO_HTTP_CODE),
            'error' => [
                'code' => curl_errno($this->_cUrl),
                'message' => curl_error($this->_cUrl)
            ],
            'response' => $this->_expandJson($response)
        ];
    }

    /**
     * 發出 POST 請求
     *
     * @param  string       $url      網址
     * @param  string|null  $token    API 令牌；本類別不需要
     * @param  array|null   $payload  傳遞資料
     * @return array
     */
    public function post(string $url, ?string $token = null, ?array $payload = null): array
    {
        $this->_reset();

        $url = (!preg_match('/^http/', $url)) ? "{$this->_baseUrl}/{$url}" : $url;

        curl_setopt($this->_cUrl, CURLOPT_URL, $url);
        curl_setopt($this->_cUrl, CURLOPT_HTTPHEADER, [
            "content-type: {$this->_contentType}"
        ]);
        curl_setopt($this->_cUrl, CURLOPT_POST, true);
        if (!is_null($payload))
        {
            curl_setopt($this->_cUrl, CURLOPT_POSTFIELDS, json_encode($payload, 320));
        }

        $response = $this->_exec();

        return [
            'httpStatusCode' => curl_getinfo($this->_cUrl, CURLINFO_HTTP_CODE),
            'error' => [
                'code' => curl_errno($this->_cUrl),
                'message' => curl_error($this->_cUrl)
            ],
            'response' => $this->_expandJson($response)
        ];
    }
}
