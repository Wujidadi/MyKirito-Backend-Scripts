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

# 由命令行參數指定玩家暱稱、行動標的、是否領取樓層獎勵、是否自動復活及輸出模式
$option = getopt('', ['player:', 'action:', 'no-bonus', 'rez', 'output']);

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

# 行動標的：輸入數字 0 - 6，以逗號分隔；或以 JSON 格式指定較複雜的等級與比例，須依等級階段由低至高排序
$action = [];
$inputActionIsValid = false;
if (!isset($option['action']) || $option['action'] === '')
{
    echo CliHelper::colorText('未指定行動標的（action：須為數字 0～6 或 1h、2h、4h、8h 四種修行時數，並以逗號分隔；或以 JSON 格式指定較複雜的等級與比例），將從 7 種一般行動中隨機執行！', CLI_TEXT_CAUTION, true);
    $action = range(0, 6);
}
else
{
    # 非 JSON
    if (!is_array(json_decode($option['action'], true)))
    {
        $inputActionIsArray = false;

        $inputActions = explode(',', $option['action']);
        foreach ($inputActions as $item)
        {
            $item = trim($item);
            if (!in_array($item, $action))
            {
                if (Helper::IsInteger($item) && (int) $item >= 0 && (int) $item < count(Constant::NormalAction) && !in_array((int) $item, $action))
                {
                    $action[] = (int) $item;
                }
                else if (in_array($item, Constant::PracticeAction) && !in_array($item, $action))
                {
                    $action[] = array_search($item, Constant::AutoableAction);
                }
            }
        }

        if (count($action) > 0)
        {
            $inputActionIsValid = true;
        }
        else
        {
            echo CliHelper::colorText('行動標的（action：須為數字 0～6 或 1h、2h、4h、8h 四種修行時數，並以逗號分隔）未正確指定，將從 7 種一般行動中隨機執行！', CLI_TEXT_CAUTION, true);
            $action = range(0, 6);
        }
    }
    # JSON（等級比例行動）
    else
    {
        $inputActionIsArray = true;

        $inputActionArray = json_decode($option['action'], true);
        $tmpInputActions = [];
        foreach ($inputActionArray as $i => $actionStage)
        {
            if (is_array($actionStage))
            {
                if (isset($actionStage['MaxLevel']) && is_int($actionStage['MaxLevel']) &&
                    isset($actionStage['Actions']) && is_array($actionStage['Actions']))
                {
                    foreach ($actionStage['Actions'] as $j => $singleAction)
                    {
                        $actionIsInt = null;

                        if (isset($singleAction['Action']))
                        {
                            $item = trim($singleAction['Action']);

                            if (Helper::IsInteger($item) && (int) $item >= 0 && (int) $item < count(Constant::NormalAction))
                            {
                                $actionIsInt = true;
                            }
                            else if (in_array($item, Constant::PracticeAction))
                            {
                                $actionIsInt = false;
                            }

                            if (isset($actionIsInt))
                            {
                                # 限定行動比例必須為整數
                                if (isset($singleAction['Ratio']) && Helper::IsInteger($singleAction['Ratio']) && $singleAction['Ratio'] > 0)
                                {
                                    $ratio = (int) $singleAction['Ratio'];

                                    if (!isset($tmpInputActions[$i]))
                                    {
                                        $tmpInputActions[$i] = [
                                            'MaxLevel' => $actionStage['MaxLevel'],
                                            'Actions' => [],
                                            'FullRatio' => 0
                                        ];
                                    }

                                    $tmpInputActions[$i]['Actions'][$j] = [
                                        'Action' => $actionIsInt ? (int) $item : $item,
                                        'Ratio' => $ratio
                                    ];

                                    $tmpInputActions[$i]['FullRatio'] += $ratio;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (count($tmpInputActions) > 0)
        {
            $inputActionIsValid = true;
            $action = $tmpInputActions;
            unset($tmpInputActions);
        }
        else
        {
            echo CliHelper::colorText('行動標的 JSON 未正確指定，將從 7 種一般行動中隨機執行！', CLI_TEXT_CAUTION, true);
            $inputActionIsArray = false;
            $action = range(0, 6);
        }
    }
}
# 重構行動標的輸入參數，用於輸出日誌及自動通知
if ($inputActionIsValid)
{
    if (!isset($inputActionArray))
    {
        $inputActions = [];
        $_action = $action;
        sort($_action);
        foreach ($_action as $_act)
        {
            $inputActions[] = $_act <= 6 ? (string) $_act : Constant::AutoableAction[$_act];
        }
        $inputAction = implode(',', $inputActions);
    }
    else
    {
        $inputActions = [];
        foreach ($action as $_act)
        {
            unset($_act['FullRatio']);
            $inputActions[] = $_act;
        }
        $inputAction = json_encode($inputActions);
    }
}
else
{
    $inputAction = implode(',', $action);
}

# 領取樓層獎勵（預設為隨每次自動行動一起領取）
$noBonus = isset($option['no-bonus']);

# 自動復活（預設為不自動復活）
$resurrect = isset($option['rez']);

# 輸出模式（預設為僅寫入檔案，不顯示於終端機）
$syncOutput = isset($option['output']);

# 命令全文（用於輸出日誌及自動通知）
$argPlayer  = " --player={$player}";
$argAction  = " --action={$inputAction}";
$argNoBonus = $noBonus ? ' --no-bonus' : '';
$argRez     = $resurrect ? ' --rez' : '';
$argOutput  = $syncOutput ? ' --output' : '';
$fullCommand = "{$scriptName}{$argPlayer}{$argAction}{$argNoBonus}{$argRez}{$argOutput}";

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
                    $notificationMessage = NotificationHelper::buildNotificationMessage($notificationTitle, $fullCommand, $logMessage, 'error', $logTime);
                    TelegramBot::getInstance()->sendMessage($notificationMessage);

                    $notificationMessage = NotificationHelper::buildNotificationLogMessage($notificationMessage);
                    Logger::getInstance()->log($notificationMessage, $notificationLogFile, false, $logTime);
                }

                $exitStatus = CLI_ERROR;
                goto Endpoint;
            }
        }

        # 從玩家基本資訊中取出玩家角色、等級、最後行動時間、最後領取樓層獎勵時間、當前所在樓層與死亡狀態
        $myCharacter = explode('.', $response['avatar'])[0];
        $myLevel = $response['lv'];
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

            # 決定行動標的
            # 輸入的行動標的為數字或字串（未以 JSON 指定）
            if (!$inputActionIsArray)
            {
                # 在指定的行動標的範圍內隨機選定行動項目
                $seed = mt_rand(0, count($action) - 1);
                $actionKey = $action[$seed];
            }
            # 輸入的行動標的為陣列（以 JSON 指定）
            else
            {
                foreach ($action as $actionsByLevel)
                {
                    # 停在碰到的第一個 MaxLevel 大於等於當前等級的階段設定
                    # 當前等級超過設定的 MaxLevel 最大值（未設定直到 70 級的行動比例）時，將 MaxLevel 為最大值（即 for loop 最後跑到）的階段比例設定
                    if ($actionsByLevel['MaxLevel'] >= $myLevel)
                    {
                        break;    
                    }
                }

                $stageActionConf = [
                    'MaxLevel' => $actionsByLevel['MaxLevel'],
                    'Action' => []
                ];

                $actionRatioCursor = 0;
                foreach ($actionsByLevel['Actions'] as $stageAction)
                {
                    for ($cur = 0; $cur < $stageAction['Ratio']; $cur++)
                    {
                        $stageActionConf['Action'][$actionRatioCursor] = $stageAction['Action'];
                        $actionRatioCursor++;
                    }
                }

                if (!isset($stageActionCounter) || $stageActionCounter >= $actionsByLevel['FullRatio'])
                {
                    $stageActionCounter = 0;
                }

                $actionKey = $stageActionConf['Action'][$stageActionCounter];
            }
            $actionAlias = Constant::AutoableAction[$actionKey];

            echo "{$stageActionCounter}: {$actionKey}, {$actionAlias}\n";

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

                    Logger::getInstance()->log($logMessage, $logFiles, true, $logTime);

                    if ($syncOutput)
                    {
                        echo CliHelper::colorText($logMessage['brief'], CLI_TEXT_INFO, true);
                    }

                    if ($inputActionIsArray)
                    {
                        # 令階段比例行動計數器加 1
                        $stageActionCounter++;
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

        # 未帶「不領取樓層獎勵」參數時自動領取樓層獎勵
        if (!$noBonus)
        {
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
