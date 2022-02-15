<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use Lib\Helpers\Helper;
use Lib\Helpers\CliHelper;
use Lib\Helpers\NotificationHelper;
use Lib\Log\Logger;
use Lib\FileLock;
use App\Constant;
use App\MyKirito;
use App\TelegramBot;

#========== 起點 ==========#

# 腳本名稱（含副檔名）
$scriptName = basename(__FILE__);

# 腳本名稱（不含副檔名）
$cliName = basename(__FILE__, '.php');

# 由命令行參數指定玩家暱稱、行動標的、是否自動復活及輸出模式
$option = getopt('', ['player:', 'action:', 'rez', 'output']);

# 玩家暱稱
if (!isset($option['player']) || $option['player'] === '')
{
    echo CliHelper::colorText('必須指定玩家暱稱（player）！', CLI_TEXT_ERROR, true);
    exit(CLI_ERROR);
}
$player = $option['player'];

# 玩家暱稱必須在 configs/Players.php 中有建檔
if (!in_array($player, array_keys(PLAYER)))
{
    echo CliHelper::colorText('玩家暱稱尚未納入紀錄！', CLI_TEXT_ERROR, true);
    exit(CLI_ERROR);
}

# 檔案鎖名稱
$fileLockName = "{$cliName}_{$player}";

# 宣告 MyKirito 物件實例
$myKirito = MyKirito::getInstance($player);

# 自動腳本詳略日誌檔案
$logFiles = [
    'brief' => STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . $cliName . DIRECTORY_SEPARATOR . $player . '.log',
    'detail' => LOG_DIR . DIRECTORY_SEPARATOR . $cliName . DIRECTORY_SEPARATOR . $player . '.log'
];

# Telegram 自動通知日誌檔案
$notificationLogFile = TELEGRAM_LOG_PATH . DIRECTORY_SEPARATOR . $cliName . DIRECTORY_SEPARATOR . $player . '.log';

# 行動標的：輸入數字 0 - 6，以逗號分隔
$action = [];
if (!isset($option['action']) || $option['action'] === '')
{
    echo CliHelper::colorText('未指定行動標的（action：須為數字 0～6 並以逗號分隔），將從 7 種一般行動中隨機執行！', CLI_TEXT_CAUTION, true);
    $action = range(0, 6);
}
else
{
    $inputActions = explode(',', $option['action']);
    foreach ($inputActions as $item)
    {
        $item = trim($item);
        if (Helper::isInteger($item) && (int) $item >= 0 && (int) $item < count(Constant::NormalAction) && !in_array((int) $item, $action))
        {
            $action[] = (int) $item;
        }
    }
    if (count($action) <= 0)
    {
        echo CliHelper::colorText('行動標的（action：須為數字 0～6 並以逗號分隔）未正確指定，將從 7 種一般行動中隨機執行！', CLI_TEXT_CAUTION, true);
        $action = range(0, 6);
    }
}

# 自動復活（預設為不自動復活）
$resurrect = isset($option['rez']);

# 輸出模式（預設為同步寫入檔案並顯示於終端機）
$syncOutput = isset($option['output']);

# 命令全文（用於輸出日誌及自動通知）
$argPlayer = " --player=\"{$player}\"";
$argAction = ' --action=' . implode(',', $action);
$argRez    = $resurrect ? ' --rez' : '';
$argOutput = $syncOutput ? ' --output' : '';
$fullCommand = "{$scriptName}{$argPlayer}{$argAction}{$argRez}{$argOutput}";

# 自動通知訊息的標題（首段）
$notificationTitle = '自動行動腳本停止執行';

# 加檔案鎖防止程序重複執行
FileLock::getInstance()->lock($fileLockName, $cliName);

#========== 循環執行起點 ==========#

try
{
    # 循環執行
    while (true)
    {
        # 重試次數
        $retry = 0;

        # 在最大重試次數內，發送請求以取得玩家基本資訊
        while ($retry < Constant::MaxRetry)
        {
            $result = $myKirito->getPersonalData();

            if ($result['httpStatusCode'] !== 200 || ($result['error']['code'] !== 0 || $result['error']['message'] !== ''))
            {
                $context = 'MyKirito::getPersonalData';
                CliHelper::logError($result, $logFiles, $context, $syncOutput);

                $retry++;

                # 每次重試間隔
                sleep(Constant::RetryInterval);
            }
            else
            {
                $response = $result['response'];
                break;
            }

            # 達到重試次數上限仍然失敗
            if ($retry >= Constant::MaxRetry)
            {
                $logTime = Helper::Time();
                $logMessage = "{$context} 重試 {$retry} 次失敗";
                Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, CLI_TEXT_ERROR, true);
                }

                if (USE_TELEGRAM_BOT)
                {
                    $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $errorMessage, 'error', $logTime);
                    TelegramBot::getInstance()->sendMessage($notificationMessage);

                    $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
                    Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
                }

                $exitStatus = CLI_ERROR;
                goto Endpoint;
            }
        }

        # 從玩家基本資訊中取出玩家角色、最後行動時間、最後領取樓層獎勵時間、當前所在樓層與死亡狀態
        $myCharacter = explode('.', $response['avatar'])[0];
        $lastAction = $response['lastAction'];
        $lastFloorBonus = $response['lastFloorBonus'] ?? null;
        $floor = $response['floor'];
        $playerIsDead = $response['dead'];

        # 取得現在時間
        $now = Helper::Timestamp() * 1000;

        # 已過行動冷卻時間
        if (($now - $lastAction) > (Constant::ActionCD + Constant::CooldownBuffer))
        {
            # 確認玩家本身是否存活
            if ($playerIsDead)
            {
                $logTime = Helper::Time();
                $logMessage = "玩家 {$player} 已死亡";
                Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, CLI_TEXT_WARNING, true);
                }

                # 自動復活
                if ($resurrect)
                {
                    $rezResult = $myKirito->autoRez($player, $myCharacter, $logFiles, $syncOutput);
                    if (!$rezResult)
                    {
                        if (USE_TELEGRAM_BOT)
                        {
                            $message = "玩家 {$player} 已死亡，自動復活失敗";
                            $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $message, 'error', $logTime);
                            TelegramBot::getInstance()->sendMessage($notificationMessage);
    
                            $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
                            Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
                        }

                        $exitStatus = CLI_ERROR;
                        goto Endpoint;
                    }
                }
                # 不自動復活
                else
                {
                    $logTime = Helper::Time();
                    $logMessage = "不自動復活玩家 {$player}";
                    Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

                    if ($syncOutput)
                    {
                        echo CliHelper::colorText($logMessage, CLI_TEXT_WARNING, true);
                    }

                    if (USE_TELEGRAM_BOT)
                    {
                        $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $logMessage, 'normal', $logTime);
                        TelegramBot::getInstance()->sendMessage($notificationMessage);

                        $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
                        Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
                    }

                    $exitStatus = CLI_OK;
                    goto Endpoint;
                }
            }

            # 在指定的行動標的範圍內隨機選定行動項目
            $seed = mt_rand(0, count($action) - 1);
            $actionKey = $action[$seed];
            $actionAlias = Constant::NormalAction[$actionKey];

            # 重試次數
            $retry = 0;

            # 執行上下文
            $context = "MyKirito::doAction('{$actionAlias}')";

            # 在最大重試次數內，發送行動請求
            while ($retry < Constant::MaxRetry)
            {
                # 行動！
                $result = $myKirito->doAction($actionAlias);

                if ($result['httpStatusCode'] !== 200 || ($result['error']['code'] !== 0 || $result['error']['message'] !== ''))
                {
                    CliHelper::logError($result, $logFiles, $context, $syncOutput);

                    $retry++;

                    # 每次重試間隔
                    sleep(Constant::RetryInterval);
                }
                else
                {
                    $logTime = Helper::Time();
                    $jsonResult = json_encode($result, 320);

                    $actionName = Constant::ActionName[$actionAlias];

                    $logMessage = [
                        'brief' => $actionName,
                        'detail' => $jsonResult
                    ];

                    # 記錄解鎖角色
                    if (isset($result['response']['myKirito']['unlockedCharacters']))
                    {
                        $characterName = MyKirito::getNewestUnlockedCharacter($result)['name'];
                        $logMessage['brief'] = "{$logMessage['brief']}，解鎖角色：{$characterName}";
                    }

                    Logger::getInstance()->log($logMessage, $logFiles, true, $logTime);

                    if ($syncOutput)
                    {
                        echo CliHelper::colorText($logMessage['brief'], CLI_TEXT_INFO, true);
                    }

                    break;
                }
            }

            # 達到重試次數上限仍然失敗
            if ($retry >= Constant::MaxRetry)
            {
                $logTime = Helper::Time();
                $logMessage = "{$context} 重試 {$retry} 次失敗";
                Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, CLI_TEXT_ERROR, true);
                }
            }
        }

        # 嘗試行動與領取樓層獎勵之間的時間間隔
        sleep(Constant::ActionBonusInterval);

        # 更新現在時間
        $now = Helper::Timestamp() * 1000;

        # 當前位於 1 層以上，未曾領取過樓層獎勵，或已超過領取獎勵冷卻時間時，發送領取樓層獎勵請求
        if ($floor > 0 &&
            (is_null($lastFloorBonus) ||
             (!is_null($lastFloorBonus) && ($now - $lastFloorBonus) > (Constant::FloorBonusCD + Constant::CooldownBuffer))))
        {
            $actionAlias = 'Bonus';

            # 重設重試次數
            $retry = 0;

            # 執行上下文
            $context = "MyKirito::doAction('{$actionAlias}')";

            # 在最大重試次數內，發送行動請求
            while ($retry < Constant::MaxRetry)
            {
                # 領！
                $result = $myKirito->doAction($actionAlias);

                if ($result['httpStatusCode'] !== 200 || ($result['error']['code'] !== 0 || $result['error']['message'] !== ''))
                {
                    CliHelper::logError($result, $logFiles, $context, $syncOutput);

                    $retry++;

                    # 每次重試間隔
                    sleep(Constant::RetryInterval);
                }
                else
                {
                    $logTime = Helper::Time();
                    $jsonResult = json_encode($result, 320);

                    $actionName = Constant::ActionName[$actionAlias];

                    $logMessage = [
                        'brief' => $actionName,
                        'detail' => $jsonResult
                    ];

                    Logger::getInstance()->log($logMessage, $logFiles, true, $logTime);

                    if ($syncOutput)
                    {
                        echo CliHelper::colorText($logMessage['brief'], CLI_TEXT_INFO, true);
                    }

                    break;
                }
            }

            # 達到重試次數上限仍然失敗
            if ($retry >= Constant::MaxRetry)
            {
                $logTime = Helper::Time();

                $logMessage = "[{$logTime}] MyKirito::doAction('{$actionAlias}') 重試 {$retry} 次失敗";

                file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, CLI_TEXT_ERROR, true);
                }
            }
        }

        # 每次執行間隔
        sleep(Constant::ActionRoundInterval);
    }
}
catch (Throwable $ex)
{
    $logTime = Helper::Time();

    $exType = get_class($ex);
    $exCode = $ex->getCode();
    $exMessage = $ex->getMessage();
    $logMessage = "{$exType} {$exCode} {$exMessage}";

    Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

    if (USE_TELEGRAM_BOT)
    {
        $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $logMessage, 'error', $logTime);
        TelegramBot::getInstance()->sendMessage($notificationMessage);

        $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
        Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
    }

    $exitStatus = CLI_ERROR;
    goto Endpoint;
}

$logTime = Helper::Time();
$logMessage = '跳出 while loop';
Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

if (USE_TELEGRAM_BOT)
{
    # 不自然跳出 while loop，仍須發送通知
    $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $logMessage, 'abnormal', $logTime);
    TelegramBot::getInstance()->sendMessage($notificationMessage);

    $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
    Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
}

$exitStatus = CLI_ABNORMAL;
goto Endpoint;

#========== 終點 ==========#

Endpoint:

FileLock::getInstance()->unlock();

unset($myKirito);

exit($exitStatus);
