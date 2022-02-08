<?php

namespace App;

use Lib\MyKiritoAPI;

/**
 * 「我的桐人」動作處理類別
 */
class MyKirito
{
    /**
     * API 連接物件
     *
     * @var MyKiritoAPI
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
        $this->_conn = MyKiritoAPI::getInstance();
    }

    /**
     * 取得玩家個人資料
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getPersonalData(string $player): array
    {
        return $this->_conn->get('my-kirito', PLAYER[$player]['Token']);
    }

    /**
     * 更新玩家個人狀態
     *
     * @param  string  $player  玩家暱稱
     * @param  string  $status  玩家狀態
     * @return array
     */
    public function updatePersonalStatus(string $player, string $status): array
    {
        $payload = [
            'status' => $status
        ];
        return $this->_conn->post('my-kirito/status', PLAYER[$player]['Token'], $payload);
    }

    /**
     * 取得玩家成就資料
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getAchievements(string $player): array
    {
        return $this->_conn->get('achievements', PLAYER[$player]['Token']);
    }

    /**
     * 取得名人堂資料
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getHallOfFame(string $player): array
    {
        return $this->_conn->get('hall-of-fame', PLAYER[$player]['Token']);
    }
}
