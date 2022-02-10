<?php

namespace Lib;

/**
 * 測試 API 類別
 */
class FakeAPI
{
    /**
     * 偽回應內容檔案路徑
     *
     * @var string
     */
    protected $_contentFile;

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
        $this->_init();
    }

    /**
     * 初始化
     *
     * @return void
     */
    protected function _init()
    {
        $this->_contentFile = STORAGE_DIR . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'FakeAPI' . DIRECTORY_SEPARATOR . 'Content.json';
    }

    /**
     * 發送請求，實為取得偽回應檔案的內容
     *
     * @return array|string
     */
    public function request(): array|string
    {
        $content = file_get_contents($this->_contentFile);
        return [
            'httpStatusCode' => 200,
            'error' => [
                'code' => 0,
                'message' => ''
            ],
            'response' => json_decode($content, true) ?? $content
        ];
    }
}
