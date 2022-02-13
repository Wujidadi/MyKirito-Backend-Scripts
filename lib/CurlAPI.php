<?php

namespace Lib;

/**
 * cURL 連線處理元類別
 */
abstract class CurlAPI
{
    /**
     * 連線目標主機名稱
     *
     * @var string
     */
    protected $_host;

    /**
     * 連線目標 API 連結
     *
     * @var string
     */
    protected $_baseUrl;

    /**
     * 連線目標 HTTP 參照位址
     *
     * @var string
     */
    protected $_referer;

    /**
     * 連線目標 HTTP 內容類型
     *
     * @var string
     */
    protected $_contentType;

    /**
     * 連線目標 HTTP 使用者代理
     *
     * @var string
     */
    protected $_userAgent;

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
    protected static $_uniqueInstance;

    /**
     * 取得物件實例
     *
     * @return self
     */
    abstract public static function getInstance();

    /**
     * 建構子
     *
     * @return void
     */
    protected function __construct() {}

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
     * @param  string|null  $token  API 令牌
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
     * @param  string      $token    API 令牌
     * @param  array|null  $payload  傳遞資料
     * @return array
     */
    public function post(string $url, string $token, ?array $payload = null): array
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
