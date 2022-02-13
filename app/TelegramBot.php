<?php

namespace App;

use Lib\TelegramBotAPI;

/**
 * Telegram 自動通知機器人處理類別
 */
class TelegramBot
{
    /**
     * API 連接物件
     *
     * @var TelegramBotAPI
     */
    protected $_conn;

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
        $this->_conn = TelegramBotAPI::getInstance();
    }

    /**
     * 傳送訊息
     *
     * @param  string  $messageText  訊息內容
     * @return array
     */
    public function sendMessage(string $messageText): array
    {
        $token = TELEGRAM_BOT['Token'];
        $payload = [
            'chat_id' => TELEGRAM_GROUP['ID'],
            'text' => $messageText
        ];
        return $this->_conn->post("bot{$token}/sendMessage", null, $payload);
    }
}
