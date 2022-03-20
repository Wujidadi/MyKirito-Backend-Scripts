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

# 由命令行參數指定玩家暱稱、對手暱稱、挑戰類型、喊話內容、是否自動復活及輸出模式
$option = getopt('', ['player:', 'opp:', 'type:', 'shout:', 'rez', 'output']);

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

# 從暱稱指定對手
if (!isset($option['opp']) || $option['opp'] === '')
{
    echo CliHelper::colorText('必須指定對手暱稱（opp）！', CLI_TEXT_ERROR, true);
    unset($myKirito);
    exit(CLI_ERROR);
}
$opponents = explode(',', $option['opp']);
foreach ($opponents as $opp)
{
    $invalidOpponent = false;
    $warningMessage = '';

    $result = $myKirito->getPlayerByName($opp);
    if (!isset($result['response']['userList']) || count($result['response']['userList']) <= 0)
    {
        $invalidOpponent = true;
        $warningMessage = "對手玩家 {$opp} 不存在";
    }
    else if ($result['response']['userList'][0]['color'] === 'grey')
    {
        $invalidOpponent = true;
        $warningMessage = "對手玩家 {$opp} 已死亡";
    }
    else if ($result['response']['userList'][0]['nickname'] === $player)
    {
        $invalidOpponent = true;
        $warningMessage = "你怎麼能跟自己單挑呢？";
    }

    if ($invalidOpponent)
    {
        $index = array_search($opp, $opponents);
        unset($opponents[$index]);
        echo CliHelper::colorText($warningMessage, CLI_TEXT_CAUTION, true);
    }
}
$opponents = array_values($opponents);
if (count($opponents) <= 0)
{
    echo CliHelper::colorText('指定的對手玩家要不都不存在，要不就是死了，不然就是你亂定！', CLI_TEXT_ERROR, true);
    unset($myKirito);
    exit(CLI_ERROR);
}
$opponent = implode(',', $opponents);

# 挑戰類型
if (!isset($option['type']) || $option['type'] === '')
{
    echo CliHelper::colorText('必須指定挑戰類型（type：須為數字 0～3 其中之一）！', CLI_TEXT_ERROR, true);
    unset($myKirito);
    exit(CLI_ERROR);
}
else if (!Helper::IsInteger($option['type']) || (int) $option['type'] < 0 || (int) $option['type'] > 3)
{
    echo CliHelper::colorText('挑戰類型（type：須為數字 0～3 其中之一）未正確指定！', CLI_TEXT_ERROR, true);
    unset($myKirito);
    exit(CLI_ERROR);
}
$challengeType = (int) $option['type'];

# 喊話
$shout = $option['shout'] ?? '';

# 自動復活（預設為不自動復活）
$resurrect = isset($option['rez']);

# 輸出模式（預設為僅寫入檔案，不顯示於終端機）
$syncOutput = isset($option['output']);

# 命令全文（用於輸出日誌及自動通知）
$argPlayer = " --player={$player}";
$argOpp    = " --opp={$opponent}";
$argType   = " --type={$option['type']}";
$argShout  = $shout == '' ? '' : " --shout=\"{$shout}\"";
$argRez    = $resurrect ? ' --rez' : '';
$argOutput = $syncOutput ? ' --output' : '';
$fullCommand = "{$scriptName}{$argPlayer}{$argOpp}{$argType}{$argShout}{$argRez}{$argOutput}";

# 自動通知訊息的標題（首段）
$notificationTitle = '自動挑戰腳本停止執行';

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
                    $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $logMessage, 'error', $logTime);
                    TelegramBot::getInstance()->sendMessage($notificationMessage);

                    $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
                    Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
                }

                $exitStatus = CLI_ERROR;
                goto Endpoint;
            }
        }

        # 從玩家基本資訊中取出玩家角色、最後挑戰時間與死亡狀態
        $myCharacter = explode('.', $response['avatar'])[0];
        $lastChallenge = $response['lastChallenge'];
        $playerIsDead = $response['dead'];

        # 更新現在時間
        $now = Helper::Timestamp() * 1000;

        # 已過挑戰冷卻時間
        if (($now - $lastChallenge) > (Constant::ChallengeCD + Constant::CooldownBuffer))
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
                        $message = "玩家 {$player} 已死亡，不自動復活";
                        $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $message, 'normal', $logTime);
                        TelegramBot::getInstance()->sendMessage($notificationMessage);

                        $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
                        Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
                    }

                    $exitStatus = CLI_OK;
                    goto Endpoint;
                }
            }

            # 隨機抽選一位幸運對手
            $oppKey = count($opponents) > 1 ? mt_rand(0, count($opponents) - 1) : 0;
            $opponent = $opponents[$oppKey];

            # 確認對手存活
            $result = $myKirito->getPlayerByName($opponent);

            # 定位對手玩家角色
            $oppCharacter = explode('.', $result['response']['userList'][0]['avatar'])[0];

            # 對手為死亡狀態
            if ($result['response']['userList'][0]['color'] === 'grey')
            {
                $logTime = Helper::Time();
                $logMessage = "對手玩家 {$opponent} 已死亡";
                Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, CLI_TEXT_WARNING, true);
                }

                # 若我們存有對手玩家的 ID 與 Token，可自動復活
                if ($resurrect && in_array($opponent, array_keys(PLAYER)))
                {
                    # 自動復活
                    $rezResult = $myKirito->autoRez($opponent, $oppCharacter, $logFiles, $syncOutput, true);
                }
                # 不自動復活
                else
                {
                    $logTime = Helper::Time();
                    $logMessage = "不自動復活對手玩家 {$opponent}";
                    Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

                    if ($syncOutput)
                    {
                        echo CliHelper::colorText($logMessage, CLI_TEXT_WARNING, true);
                    }

                    # 將對手玩家暱稱從對手清單中移除
                    unset($opponents[$oppKey]);
                    $opponents = array_values($opponents);

                    # 對手清單被清空時跳出
                    if (count($opponents) <= 0)
                    {
                        $message = '所有對手玩家均已死亡，且不自動復活';

                        if ($syncOutput)
                        {
                            echo CliHelper::colorText($message, CLI_TEXT_WARNING, true);
                        }

                        if (USE_TELEGRAM_BOT)
                        {
                            $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $message, 'normal', $logTime);
                            TelegramBot::getInstance()->sendMessage($notificationMessage);

                            $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
                            Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
                        }

                        $exitStatus = CLI_OK;
                        goto Endpoint;
                    }
                }
            }

            # 重試次數
            $retry = 0;

            # 執行上下文
            $context = "MyKirito::challenge('{$opponent}', {$challengeType}, '{$shout}')";

            # 在最大重試次數內，發送挑戰請求
            while ($retry < Constant::MaxRetry)
            {
                # 對戰！
                $result = $myKirito->challenge($opponent, $challengeType, $shout);

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

                    $response = $result['response'];
                    $challengeResult = $response['result'];
                    $playerIsKilled = $result['dead']['me'];
                    $opponentIsKilled = $result['dead']['opponent'];

                    $logMessage = [
                        'brief' => "{$player} vs {$opponent} {$challengeResult}",
                        'detail' => $jsonResult
                    ];

                    # 記錄經驗值
                    if (isset($result['response']['gained']['exp']) && isset($result['response']['gained']['exp']) > 0)
                    {
                        $logMessage['brief'] = "{$logMessage['brief']}，獲得 {$result['response']['gained']['exp']} 點經驗值";
                    }

                    # 記錄升級
                    if (isset($result['response']['gained']['prevLV']) && isset($result['response']['gained']['nextLV']) &&
                        $result['response']['gained']['prevLV'] !== $result['response']['gained']['nextLV'])
                    {
                        $logMessage['brief'] = "{$logMessage['brief']}，升到 {$result['response']['gained']['nextLV']} 級";
                    }

                    # 記錄解鎖角色
                    if (isset($result['response']['myKirito']['unlockedCharacters']))
                    {
                        $characterName = MyKirito::getNewestUnlockedCharacter($result)['name'];
                        $logMessage['brief'] = "{$logMessage['brief']}，解鎖角色：{$characterName}";
                    }

                    # 玩家本身死亡，記錄死亡狀態並視環境自動復活
                    if ($playerIsKilled)
                    {
                        $logMessage['brief'] = "{$logMessage['brief']}，你死了";
                        Logger::getInstance()->log($logMessage, $logFiles, true, $logTime);

                        if ($syncOutput)
                        {
                            echo CliHelper::colorText($logMessage['brief'], CLI_TEXT_WARNING, true);
                        }

                        # 自動復活
                        if ($resurrect)
                        {
                            $rezResult = $myKirito->autoRez($player, $myCharacter, $logFiles, $syncOutput);

                            if (!$rezResult)
                            {
                                if (USE_TELEGRAM_BOT)
                                {
                                    $message = "挑戰失敗，玩家 {$player} 死亡，自動復活失敗";
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
                            $logMessage = "挑戰失敗，玩家 {$player} 死亡，不自動復活";
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
                    # 對手玩家死亡，記錄死亡狀態並視環境自動復活
                    else if ($opponentIsKilled)
                    {
                        $logMessage['brief'] = "{$logMessage['brief']}，{$opponent} 死了";
                        Logger::getInstance()->log($logMessage, $logFiles, true, $logTime);

                        if ($syncOutput)
                        {
                            echo CliHelper::colorText($logMessage['brief'], CLI_TEXT_WARNING, true);
                        }

                        # 自動復活
                        if ($resurrect && in_array($opponent, array_keys(PLAYER)))
                        {
                            $myKirito->autoRez($opponent, $oppCharacter, $logFiles, $syncOutput, true);
                        }
                        # 不自動復活
                        else
                        {
                            $logTime = Helper::Time();
                            $logMessage = "挑戰勝利，對手玩家 {$opponent} 死亡，不自動復活";
                            Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

                            if ($syncOutput)
                            {
                                echo CliHelper::colorText($logMessage, CLI_TEXT_WARNING, true);
                            }

                            # 將對手玩家暱稱從對手清單中移除
                            unset($opponents[$oppKey]);
                            $opponents = array_values($opponents);

                            # 對手清單被清空時跳出
                            if (count($opponents) <= 0)
                            {
                                $message = '所有對手玩家均已死亡，且不自動復活';

                                if ($syncOutput)
                                {
                                    echo CliHelper::colorText($message, CLI_TEXT_WARNING, true);
                                }

                                if (USE_TELEGRAM_BOT)
                                {
                                    $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $message, 'normal', $logTime);
                                    TelegramBot::getInstance()->sendMessage($notificationMessage);

                                    $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
                                    Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
                                }

                                $exitStatus = CLI_OK;
                                goto Endpoint;
                            }
                        }
                    }
                    # 無人死亡
                    else
                    {
                        Logger::getInstance()->log($logMessage, $logFiles, true, $logTime);

                        if ($syncOutput)
                        {
                            echo CliHelper::colorText($logMessage['brief'], CLI_TEXT_INFO, true);
                        }
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

        # 每次執行間隔
        sleep(Constant::ChallengeInterval);
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

    if ($syncOutput)
    {
        echo CliHelper::colorText($logMessage, CLI_TEXT_ERROR, true);
    }

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

if ($syncOutput)
{
    echo CliHelper::colorText($logMessage, CLI_TEXT_ERROR, true);
}

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
