<?php

use Lib\Helpers\Helper;

/**
 * 產生玩家資料 PHP 設定檔
 *
 * @return void
 */
function buildPlayerConfig(): void
{
    $configFile = STORAGE_DIR . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'Players.json';
    $configExampleFile = STORAGE_DIR . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . 'Players.json';
    if (!is_file($configFile))
    {
        shell_exec("cp {$configExampleFile} {$configFile}");
    }

    $configJson = json_decode(file_get_contents($configFile), true);

    $config = Helper::varExport($configJson) ?? [];

    $code = <<<PHP
    <?php

    /**
     * 玩家 ID 及 Token
     *
     * @var string[]
     */
    define('PLAYER', {$config});

    PHP;

    $configPhpFile = CONFIG_DIR . DIRECTORY_SEPARATOR . 'Players.php';

    file_put_contents($configPhpFile, $code);
}

/**
 * 產生 Telegram 機器人 PHP 設定檔
 *
 * @return void
 */
function buildTelegramBotConfig(): void
{
    $configFile = STORAGE_DIR . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'TelegramBot.json';
    $configExampleFile = STORAGE_DIR . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . 'TelegramBot.json';
    if (!is_file($configFile))
    {
        shell_exec("cp {$configExampleFile} {$configFile}");
    }

    $configJson = json_decode(file_get_contents($configFile), true);
    
    if ($configJson)
    {
        $useTelegramBot = $configJson['Use'] ? 'true' : 'false';

        $botId    = $configJson['Bot']['ID']    ?? 0;
        $botName  = $configJson['Bot']['Name']  ?? '';
        $botToken = $configJson['Bot']['Token'] ?? '';

        $groupId    = $configJson['Group']['ID']    ?? -1;
        $groupTitle = $configJson['Group']['Title'] ?? '';

        # 轉義單引號
        $botName    = str_replace('\'', '\\\'', $botName);
        $groupTitle = str_replace('\'', '\\\'', $groupTitle);
    }

    $code = <<<PHP
    <?php

    /**
     * 是否啟用 Telegram 自動通知機器人
     *
     * @var boolean
     */
    define('USE_TELEGRAM_BOT', {$useTelegramBot});

    /**
     * Telegram 自動通知日誌路徑
     *
     * @var string
     */
    define('TELEGRAM_LOG_PATH', LOG_DIR . DIRECTORY_SEPARATOR . 'TelegramBot');

    /**
     * Telegram 機器人參數
     *
     * @var array
     */
    define('TELEGRAM_BOT', [

        # 機器人 ID
        'ID' => {$botId},

        # 機器人名稱
        'Name' => '{$botName}',

        # 機器人 API Token
        'Token' => '{$botToken}'

    ]);

    /**
     * Telegram 通知群組參數
     *
     * @var array
     */
    define('TELEGRAM_GROUP', [

        # 群組 ID
        'ID' => {$groupId},

        # 群組名稱
        'Title' => '{$groupTitle}'

    ]);

    PHP;

    $configPhpFile = CONFIG_DIR . DIRECTORY_SEPARATOR . 'TelegramBot.php';

    file_put_contents($configPhpFile, $code);
}
