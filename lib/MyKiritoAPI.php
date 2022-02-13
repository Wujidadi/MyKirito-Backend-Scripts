<?php

namespace Lib;

use Lib\CurlAPI;

/**
 * 「我的桐人」API 連接類別
 */
class MyKiritoAPI extends CurlAPI
{
    protected $_host = 'https://mykirito.com';
    protected $_baseUrl = 'https://mykirito.com/api';
    protected $_referer = 'https://mykirito.com/';

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
        // $this->_init();
    }

    /**
     * 發出 GET 請求
     *
     * @param  string       $url    網址
     * @param  string|null  $token  玩家 Token
     * @return array
     */
    public function get(string $url, ?string $token = null): array
    {
        return parent::get($url, $token);
    }

    /**
     * 發出 POST 請求
     *
     * @param  string      $url      網址
     * @param  string      $token    玩家 Token
     * @param  array|null  $payload  傳遞資料
     * @return array
     */
    public function post(string $url, string $token, ?array $payload = null): array
    {
        return parent::post($url, $token, $payload);
    }
}
