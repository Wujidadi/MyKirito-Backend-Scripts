<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use Lib\Helper;
use Lib\CliHelper;
use Lib\FileLock;
use App\Constant;
use App\MyKirito;
use App\TelegramBot;

#========== 起點 ==========#

# 腳本名稱
$scriptName = basename(__FILE__);

# 由命令行參數指定玩家暱稱、對手暱稱、挑戰類型、喊話內容、是否自動復活及輸出模式
$option = getopt('', ['player:', 'opp:', 'type:', 'shout:', 'rez', 'output']);

# 玩家暱稱
if (!isset($option['player']) || $option['player'] === '')
{
    echo CliHelper::colorText('必須指定玩家暱稱（player）！', '#ff8080', true);
    exit(CLI_ERROR);
}
$player = $option['player'];

# 玩家暱稱必須在 configs/Players.php 中有建檔
if (!in_array($player, array_keys(PLAYER)))
{
    echo CliHelper::colorText('玩家暱稱尚未納入紀錄！', '#ff8080', true);
    exit(CLI_ERROR);
}

# 檔案鎖名稱
$fileLockName = basename(__FILE__, '.php') . '_' . $player;

# 簡要日誌檔案
$logFile = STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . 'AutoChallenge' . DIRECTORY_SEPARATOR . $player . '.log';

# 詳細日誌檔案
$detailLogFile = LOG_DIR . DIRECTORY_SEPARATOR . 'AutoChallenge' . DIRECTORY_SEPARATOR . $player . '.log';

# Telegram 自動通知日誌檔案
$notificationLogFile = TELEGRAM_LOG_PATH . DIRECTORY_SEPARATOR . 'AutoChallenge' . DIRECTORY_SEPARATOR . $player . '.log';

# 從暱稱指定對手
if (!isset($option['opp']) || $option['opp'] === '')
{
    echo CliHelper::colorText('必須指定對手暱稱（opp）！', '#ff8080', true);
    exit(CLI_ERROR);
}
$opponents = explode(',', $option['opp']);
foreach ($opponents as $opp)
{
    $result = MyKirito::getInstance()->getPlayerByName($player, $opp);
    if (!isset($result['response']['userList']) || count($result['response']['userList']) <= 0)
    {
        $index = array_search($opp, $opponents);
        unset($opponents[$index]);
        echo CliHelper::colorText("對手玩家 {$opp} 不存在", '#ecaa65', true);
    }
    else if ($result['response']['userList'][0]['color'] === 'grey')
    {
        $index = array_search($opp, $opponents);
        unset($opponents[$index]);
        echo CliHelper::colorText("對手玩家 {$opp} 已死亡", '#ecaa65', true);
    }
    else if ($result['response']['userList'][0]['nickname'] === $player)
    {
        $index = array_search($opp, $opponents);
        unset($opponents[$index]);
        echo CliHelper::colorText("你怎麼能跟自己單挑呢？", '#ecaa65', true);
    }
}
$opponents = array_values($opponents);
if (count($opponents) <= 0)
{
    echo CliHelper::colorText('指定的對手玩家要不都不存在，要不就是死了，不然就是你亂定！', '#ff8080', true);
    exit(CLI_ERROR);
}

# 挑戰類型
if (!isset($option['type']) || $option['type'] === '')
{
    echo CliHelper::colorText('必須指定挑戰類型（type：須為數字 0～3 其中之一）！', '#ff8080', true);
    exit(CLI_ERROR);
}
else if (!Helper::isInteger($option['type']) || (int) $option['type'] < 0 || (int) $option['type'] > 3)
{
    echo CliHelper::colorText('挑戰類型（type：須為數字 0～3 其中之一）未正確指定！', '#ff8080', true);
    exit(CLI_ERROR);
}
$challengeType = (int) $option['type'];

# 喊話
$shout = $option['shout'] ?? '';

# 自動復活（預設為不自動復活）
$resurrect = isset($option['rez']);

# 輸出模式（預設為同步寫入檔案並顯示於終端機）
$syncOutput = isset($option['output']);

# 命令全文（用於輸出日誌及自動通知）
$argPlayer = " --player=\"{$player}\"";
$argOpp    = ' --opp="' . implode(',', $opponents) . '"';
$argType   = " --type={$option['type']}";
$argShout  = $shout == '' ? '' : " --shout=\"{$shout}\"";
$argRez    = $resurrect ? ' --rez' : '';
$argOutput = $syncOutput ? ' --output' : '';
$fullCommand = "{$scriptName}{$argPlayer}{$argOpp}{$argType}{$argShout}{$argRez}{$argOutput}";

# 自動通知訊息的標題（首段）
$notificationTitle = '自動挑戰腳本停止執行';

# 加檔案鎖防止程序重複執行
FileLock::getInstance()->lock($fileLockName, 'AutoChallenge');

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
            $result = MyKirito::getInstance()->getPersonalData($player);

            if ($result['httpStatusCode'] !== 200 || ($result['error']['code'] !== 0 || $result['error']['message'] !== ''))
            {
                if ($result['httpStatusCode'] !== 200)
                {
                    $logTime = Helper::Time();

                    $errorMessage = "MyKirito::getPersonalData HTTP 狀態碼：{$result['httpStatusCode']}";
                    echo CliHelper::colorText($errorMessage, '#ff8080', true);

                    $logMessage = "[{$logTime}] {$errorMessage}";
                    $detailLogMessage = "[{$logTime}] " . json_encode($result, 320);

                    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                    file_put_contents($detailLogFile, $detailLogMessage . PHP_EOL, FILE_APPEND);
                }
                else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
                {
                    $logTime = Helper::Time();

                    $errorMessage = "MyKirito::getPersonalData 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}";
                    echo CliHelper::colorText($errorMessage, '#ff8080', true);

                    $logMessage = "[{$logTime}] {$errorMessage}";
                    $detailLogMessage = "[{$logTime}] " . json_encode($result, 320);

                    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                    file_put_contents($detailLogFile, $detailLogMessage . PHP_EOL, FILE_APPEND);
                }

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
                if (USE_TELEGRAM_BOT)
                {
                    $notificationMessage = CliHelper::buildNotificationMessage($notificationTitle, $fullCommand, $errorMessage, 'error', $logTime);
                    TelegramBot::getInstance()->sendMessage($notificationMessage);

                    $notificationMessage = CliHelper::buildNotificationLogMessage($notificationMessage);
                    $logMessage = "[{$logTime}] {$notificationMessage}";
                    file_put_contents($notificationLogFile, $logMessage . PHP_EOL, FILE_APPEND);
                }

                $exitStatus = CLI_ERROR;
                goto Endpoint;
            }
        }

        # 從玩家基本資訊中取出玩家角色及最後挑戰時間
        $myCharacter = explode('.', $response['avatar'])[0];
        $lastChallenge = $response['lastChallenge'];

        # 更新現在時間
        $now = Helper::Timestamp() * 1000;

        # 已過挑戰冷卻時間
        if (($now - $lastChallenge) > (Constant::ChallengeCD + Constant::CooldownBuffer))
        {
            # 確認玩家本身是否存活
            if ($response['dead'])
            {
                $logTime = Helper::Time();

                $logMessage = "[{$logTime}] 玩家 {$player} 已死亡";

                file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

                # 命令行輸出
                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, '#ecaa65', true);
                }

                # 自動復活
                if ($resurrect)
                {
                    autoRez($player, $myCharacter, $logFile, $detailLogFile, $syncOutput);
                }
                # 不自動復活
                else
                {
                    $logTime = Helper::Time();

                    $message = "不自動復活玩家 {$player}";

                    $logMessage = "[{$logTime}] {$message}";
        
                    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                    file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

                    if (USE_TELEGRAM_BOT)
                    {
                        $notificationMessage = CliHelper::buildNotificationMessage($notificationTitle, $fullCommand, $message, 'normal', $logTime);
                        TelegramBot::getInstance()->sendMessage($notificationMessage);

                        $notificationMessage = CliHelper::buildNotificationLogMessage($notificationMessage);
                        $logMessage = "[{$logTime}] {$notificationMessage}";
                        file_put_contents($notificationLogFile, $logMessage . PHP_EOL, FILE_APPEND);
                    }

                    $exitStatus = CLI_OK;
                    goto Endpoint;
                }
            }

            # 隨機抽選一位幸運對手
            if (count($opponents) > 1)
            {
                $oppKey = mt_rand(0, count($opponents) - 1);
            }
            else
            {
                $oppKey = 0;
            }
            $opponent = $opponents[$oppKey];

            # 確認對手存活
            $result = MyKirito::getInstance()->getPlayerByName($player, $opponent);

            # 定位對手玩家角色
            $oppCharacter = explode('.', $result['response']['userList'][0]['avatar'])[0];

            # 對手為死亡狀態
            if ($result['response']['userList'][0]['color'] === 'grey')
            {
                $logTime = Helper::Time();

                $logMessage = "[{$logTime}] 對手玩家 {$opponent} 已死亡";
                $detailLogMessage = "[{$logTime}] " . json_encode($result, 320);

                file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, '#ecaa65', true);
                }

                # 若我們存有對手玩家的 ID 與 Token，可自動復活
                if ($resurrect && in_array($opponent, array_keys(PLAYER)))
                {
                    # 自動復活
                    autoRez($opponent, $oppCharacter, $logFile, $detailLogFile, $syncOutput, true);
                }
                # 不自動復活
                else
                {
                    $logTime = Helper::Time();

                    $message = "不自動復活對手玩家 {$opponent}";

                    $logMessage = "[{$logTime}] {$message}";
        
                    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                    file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

                    # 將對手玩家暱稱從對手清單中移除
                    unset($opponents[$oppKey]);
                    $opponents = array_values($opponents);

                    # 對手清單被清空時跳出
                    if (count($opponents) <= 0)
                    {
                        if (USE_TELEGRAM_BOT)
                        {
                            $message = '所有對手玩家均已死亡，且不自動復活';
                            $notificationMessage = CliHelper::buildNotificationMessage($notificationTitle, $fullCommand, $message, 'normal', $logTime);
                            TelegramBot::getInstance()->sendMessage($notificationMessage);

                            $notificationMessage = CliHelper::buildNotificationLogMessage($notificationMessage);
                            $logMessage = "[{$logTime}] {$notificationMessage}";
                            file_put_contents($notificationLogFile, $logMessage . PHP_EOL, FILE_APPEND);
                        }

                        $exitStatus = CLI_OK;
                        goto Endpoint;
                    }
                }
            }

            # 重試次數
            $retry = 0;

            # 在最大重試次數內，發送挑戰請求
            while ($retry < Constant::MaxRetry)
            {
                # 對戰！
                $result = MyKirito::getInstance()->chellenge($player, $opponent, $challengeType, $shout);

                if ($result['httpStatusCode'] !== 200 || ($result['error']['code'] !== 0 || $result['error']['message'] !== ''))
                {
                    $function = "MyKirito::chellenge('{$player}', '{$opponent}', {$challengeType}, '{$shout}')";
                    CliHelper::logError($result, $function, $logFile, $detailLogFile, $syncOutput);

                    $retry++;

                    # 每次重試間隔
                    sleep(Constant::RetryInterval);
                }
                else
                {
                    $logTime = Helper::Time();

                    $response = $result['response'];
                    $challengeResult = $response['result'];

                    $logMessage = "[{$logTime}] {$player} vs {$opponent} {$challengeResult}";
                    $detailLogMessage = "[{$logTime}] " . json_encode($result, 320);

                    # 玩家本身死亡，記錄死亡狀態並視環境自動復活
                    if ($result['dead']['me'])
                    {
                        $logMessage = "{$logMessage}，你死了";

                        # 自動復活
                        if ($resurrect)
                        {
                            autoRez($player, $myCharacter, $logFile, $detailLogFile, $syncOutput);
                        }
                        # 不自動復活
                        else
                        {
                            $logTime = Helper::Time();

                            $message = "挑戰失敗，玩家 {$player} 死亡，不自動復活";

                            $logMessage = "[{$logTime}] {$message}";

                            file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                            file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

                            if (USE_TELEGRAM_BOT)
                            {
                                $notificationMessage = CliHelper::buildNotificationMessage($notificationTitle, $fullCommand, $message, 'normal', $logTime);
                                TelegramBot::getInstance()->sendMessage($notificationMessage);

                                $notificationMessage = CliHelper::buildNotificationLogMessage($notificationMessage);
                                $logMessage = "[{$logTime}] {$notificationMessage}";
                                file_put_contents($notificationLogFile, $logMessage . PHP_EOL, FILE_APPEND);
                            }

                            $exitStatus = CLI_OK;
                            goto Endpoint;
                        }
                    }

                    # 對手玩家死亡，記錄死亡狀態並視環境自動復活
                    if ($result['dead']['opponent'])
                    {
                        $logMessage = "{$logMessage}，{$opponent} 死了";

                        # 自動復活
                        if ($resurrect && in_array($opponent, array_keys(PLAYER)))
                        {
                            autoRez($opponent, $oppCharacter, $logFile, $detailLogFile, $syncOutput, true);
                        }
                        # 不自動復活
                        else
                        {
                            $logTime = Helper::Time();

                            $message = "挑戰勝利，對手玩家 {$opponent} 死亡，不自動復活";

                            $logMessage = "[{$logTime}] {$message}";

                            file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                            file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

                            # 將對手玩家暱稱從對手清單中移除
                            unset($opponents[$oppKey]);
                            $opponents = array_values($opponents);
        
                            # 對手清單被清空時跳出
                            if (count($opponents) <= 0)
                            {
                                if (USE_TELEGRAM_BOT)
                                {
                                    $message = '所有對手玩家均已死亡，且不自動復活';
                                    $notificationMessage = CliHelper::buildNotificationMessage($notificationTitle, $fullCommand, $message, 'normal', $logTime);
                                    TelegramBot::getInstance()->sendMessage($notificationMessage);
        
                                    $notificationMessage = CliHelper::buildNotificationLogMessage($notificationMessage);
                                    $logMessage = "[{$logTime}] {$notificationMessage}";
                                    file_put_contents($notificationLogFile, $logMessage . PHP_EOL, FILE_APPEND);
                                }

                                $exitStatus = CLI_OK;
                                goto Endpoint;
                            }
                        }
                    }

                    # 記錄解鎖角色
                    if (isset($result['response']['myKirito']['unlockedCharacters']))
                    {
                        $last = count($result['response']['myKirito']['unlockedCharacters']) - 1;
                        $newestUnlockedCharacter = $result['response']['myKirito']['unlockedCharacters'][$last];
                        $characterName = $newestUnlockedCharacter['name'];
                        $logMessage = "{$logMessage}，解鎖角色：{$characterName}";
                    }

                    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                    file_put_contents($detailLogFile, $detailLogMessage . PHP_EOL, FILE_APPEND);

                    if ($syncOutput)
                    {
                        # 命令行輸出
                        echo CliHelper::colorText($logMessage, '#aaffff', true);
                    }

                    break;
                }
            }

            # 達到重試次數上限仍然失敗
            if ($retry >= Constant::MaxRetry)
            {
                $logTime = Helper::Time();

                $logMessage = "[{$logTime}] {$function} 重試 {$retry} 次失敗";

                file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, '#ff8080', true);
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
    $errorMessage = "{$exType} {$exCode} {$exMessage}";

    $logMessage = "[{$logTime}] {$errorMessage}";

    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
    file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

    if (USE_TELEGRAM_BOT)
    {
        $errorMessage = "{$exType} {$exCode} {$exMessage}";
        $notificationMessage = CliHelper::buildNotificationMessage($notificationTitle, $fullCommand, $errorMessage, 'normal', $logTime);
        TelegramBot::getInstance()->sendMessage($notificationMessage);

        $notificationMessage = CliHelper::buildNotificationLogMessage($notificationMessage);
        $logMessage = "[{$logTime}] {$notificationMessage}";
        file_put_contents($notificationLogFile, $logMessage . PHP_EOL, FILE_APPEND);
    }

    $exitStatus = CLI_ERROR;
    goto Endpoint;
}

$logTime = Helper::Time();

$abnormalMessage = '跳出 while loop';
$logMessage = "[{$logTime}] {$abnormalMessage}";

file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

if (USE_TELEGRAM_BOT)
{
    # 不自然跳出 while loop，仍須發送通知
    $notificationMessage = CliHelper::buildNotificationMessage($notificationTitle, $fullCommand, $abnormalMessage, 'abnormal', $logTime);
    TelegramBot::getInstance()->sendMessage($notificationMessage);

    $notificationMessage = CliHelper::buildNotificationLogMessage($notificationMessage);
    $logMessage = "[{$logTime}] {$notificationMessage}";
    file_put_contents($notificationLogFile, $logMessage . PHP_EOL, FILE_APPEND);
}

$exitStatus = CLI_ABNORMAL;
goto Endpoint;

#========== 終點 ==========#

Endpoint:

FileLock::getInstance()->unlock();

exit($exitStatus);


#========== 輔助函數 ==========#

/**
 * 自動復活
 *
 * @param  string   $player         要復活的玩家暱稱，必須在 `configs/Players.php` 中有建檔
 * @param  string   $character      玩家的角色名稱
 * @param  string   $logFile        簡要日誌檔案路徑
 * @param  string   $detailLogFile  詳細日誌檔案路徑
 * @param  boolean  $syncOutput     是否同步輸出於終端機
 * @param  boolean  $isOpp          是否對手玩家，預設為 `false`
 * @return void
 */
function autoRez(string $player, string $character, string $logFile, string $detailLogFile, bool $syncOutput, bool $isOpp = false): void
{
    $oppNote = $isOpp ? '對手' : '';

    $logTime = Helper::Time();

    $logMessage = "[{$logTime}] 自動復活{$oppNote}玩家 {$player}……";

    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
    file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

    if ($syncOutput)
    {
        echo CliHelper::colorText($logMessage, '#eddd83', true);
    }

    # 復活：原角色原地轉生，不動用任何轉生點、洗白點數或重置樓層
    $payload = [
        'character' => $character,
        'rattrs' => [
            'hp' => 0,
            'atk' => 0,
            'def' => 0,
            'stm' => 0,
            'agi' => 0,
            'spd' => 0,
            'tec' => 0,
            'int' => 0,
            'lck' => 0
        ],
        'useReset' => false,
        'useResetBoss' => false
    ];

    # 重試次數
    $retry = 0;

    # 在最大重試次數內，發送轉生請求
    while ($retry < Constant::MaxRetry)
    {
        $result = MyKirito::getInstance()->reincarnation($player, $payload);

        if ($result['httpStatusCode'] !== 200 || ($result['error']['code'] !== 0 || $result['error']['message'] !== ''))
        {
            $logTime = Helper::Time();

            # 注意：為了簡化並節省 log 空間，此處列出的 chellenge 方法第 2 個參數並非實際應代入的 payload，而是角色名稱
            $function = "MyKirito::reincarnation('{$player}', '{$character}')";

            if ($result['httpStatusCode'] !== 200)
            {
                $logMessage = "[{$logTime}] {$function} HTTP 狀態碼：{$result['httpStatusCode']}";
                $detailLogMessage = "[{$logTime}] {$function} Response: " . json_encode($result, 320);
            }
            else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
            {
                $logMessage = "[{$logTime}] {$function} 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}";
                $detailLogMessage = "[{$logTime}] {$function} Response: " . json_encode($result, 320);
            }

            file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
            file_put_contents($detailLogFile, $detailLogMessage . PHP_EOL, FILE_APPEND);

            if ($syncOutput)
            {
                echo CliHelper::colorText($logMessage, '#ff8080', true);
            }

            $retry++;

            # 每次重試間隔
            sleep(Constant::RetryInterval);
        }
        else
        {
            $logTime = Helper::Time();

            $logMessage = "[{$logTime}] {$oppNote}玩家 {$player} 已復活";
            $detailLogMessage = "[{$logTime}] " . json_encode($result, 320);

            file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
            file_put_contents($detailLogFile, $detailLogMessage . PHP_EOL, FILE_APPEND);

            if ($syncOutput)
            {
                # 命令行輸出
                echo CliHelper::colorText($logMessage, '#eddd83', true);
            }

            break;
        }
    }

    # 達到重試次數上限仍然失敗
    if ($retry >= Constant::MaxRetry)
    {
        $logTime = Helper::Time();

        $logMessage = "[{$logTime}] {$function} 重試 {$retry} 次失敗";

        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
        file_put_contents($detailLogFile, $logMessage . PHP_EOL, FILE_APPEND);

        if ($syncOutput)
        {
            echo CliHelper::colorText($logMessage, '#ff8080', true);
        }
    }
}
