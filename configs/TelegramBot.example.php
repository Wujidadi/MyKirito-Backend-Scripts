<?php

/**
 * 是否啟用 Telegram 自動通知機器人
 *
 * @var boolean
 */
define('USE_TELEGRAM_BOT', false);

/**
 * Telegram 機器人參數
 */
define('TELEGRAM_BOT', [

    # 機器人 ID
    'ID' => 0000000000,

    # 機器人名稱
    'Name' => 'ExampleBot',

    # 機器人 API Token
    'Token' => '0000000000:Krg27E5a3KnTdImtr4xSBE36eYRVPjWbSaE'

]);

/**
 * Telegram 通知群組參數
 */
define('TELEGRAM_GROUP', [

    # 群組 ID
    'ID' => -000000001,

    # 群組名稱
    'Title' => 'Example Group'

]);
