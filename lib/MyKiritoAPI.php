<?php

namespace Lib;

/**
 * 「我的桐人」API 連接類別
 */
class MyKiritoAPI
{
    protected $_host = 'https://mykirito.com';
    protected $_baseUrl = "https://mykirito.com/api";
    protected $_referer = 'https://mykirito.com/';

    protected $_contentType = 'application/json;charset=UTF-8';
    protected $_userAgent = 'Chrome/98.0.4758.80';

    /**
     * cURL 物件
     *
     * @var \CurlHandle
     */
    protected $_cUrl;

    /**
     * 物件單一實例
     *
     * @var self|null
     */
    protected static $_uniqueInstance = null;

    /**
     * 取得物件實例
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$_uniqueInstance === null) self::$_uniqueInstance = new self;
        return self::$_uniqueInstance;
    }

    /**
     * 建構子
     *
     * @return void
     */
    protected function __construct()
    {
        // $this->_init();
    }

    /**
     * 初始化 cURL 物件
     *
     * @return void
     */
    protected function _init(): void
    {
        $this->_cUrl = curl_init();
        curl_setopt($this->_cUrl, CURLINFO_HEADER_OUT, true);
        curl_setopt($this->_cUrl, CURLOPT_HEADER, false);
        curl_setopt($this->_cUrl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_cUrl, CURLOPT_FOLLOWLOCATION, true);
    }

    /**
     * 執行 API 請求
     *
     * @return string|boolean
     */
    protected function _exec(): string|bool
    {
        return curl_exec($this->_cUrl);
    }

    /**
     * 重置 cURL 物件
     *
     * @return void
     */
    protected function _reset(): void
    {
        if (is_resource($this->_cUrl) || (!is_null($this->_cUrl) && get_class($this->_cUrl) === 'CurlHandle'))
        {
            curl_close($this->_cUrl);
        }
        $this->_init();
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
        $this->_reset();

        $url = (!preg_match('/^http/', $url)) ? "{$this->_baseUrl}/{$url}" : $url;

        curl_setopt($this->_cUrl, CURLOPT_URL, $url);
        if (!is_null($token))
        {
            curl_setopt($this->_cUrl, CURLOPT_HTTPHEADER, [
                "token: {$token}",
                "user-agent: {$this->_userAgent}"
            ]);
        }
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
     * @param  string      $url      網址
     * @param  string      $token    玩家 Token
     * @param  array|null  $payload  傳遞資料
     * @return array
     */
    public function post(string $url, string $token, ?array $payload = null)
    {
        $this->_reset();

        $url = (!preg_match('/^http/', $url)) ? "{$this->_baseUrl}/{$url}" : $url;

        curl_setopt($this->_cUrl, CURLOPT_URL, $url);
        curl_setopt($this->_cUrl, CURLOPT_HTTPHEADER, [
            "content-type: {$this->_contentType}",
            "referer: {$this->_referer}",
            "token: {$token}",
            "user-agent: {$this->_userAgent}"
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

    /**
     * 展開回應的 JSON 為陣列；若無法展開，則返回原始的回應字串
     *
     * @param  string  $data  API 回應字串
     * @return array|string
     */
    protected function _expandJson(string $data): array|string
    {
        return json_decode($data, true) ?? $data;
    }
}
