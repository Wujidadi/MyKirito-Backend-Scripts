<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use Lib\Helper;
use Lib\CliHelper;
use App\Constant;
use App\MyKirito;

# 由命令行參數指定玩家暱稱、行動標的及輸出模式
$option = getopt('', ['player:', 'action:', 'output']);
if (!isset($option['player']) || $option['player'] === '')
{
    echo CliHelper::colorText('必須指定玩家暱稱（player）！', '#ff8080', true);
    exit(1);
}

# 玩家暱稱
$player = $option['player'];

# 玩家暱稱必須在 configs/IdTokens.php 中有建檔
if (!in_array($player, array_keys(PLAYER)))
{
    echo CliHelper::colorText('玩家暱稱尚未納入紀錄！', '#ff8080', true);
    exit(1);
}

# 定義簡要 LOG 檔案並確保路徑存在
$directory = STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . 'AutoAction';
if (!is_dir($directory)) mkdir($directory);
$logFile = $directory . DIRECTORY_SEPARATOR . $player . '.log';

# 定義詳細 LOG 檔案並確保路徑存在
$directory = LOG_DIR . DIRECTORY_SEPARATOR . 'AutoAction';
if (!is_dir($directory)) mkdir($directory);
$detailLogFile = $directory . DIRECTORY_SEPARATOR . $player . '.log';

# 行動標的：輸入數字 0 - 6，以逗號分隔
$action = [];
if (!isset($option['action']) || $option['action'] === '')
{
    echo CliHelper::colorText('未指定行動標的（action：須為數字 0 - 6 並以逗號分隔），將從 7 種普通行動中隨機執行！', '#ffc080', true);
    $action = range(0, 6);
}
else
{
    $inputActions = explode(',', $option['action']);
    foreach ($inputActions as $item)
    {
        if (Helper::isInteger($item) && (int) $item >= 0 && (int) $item < count(Constant::NormalAction) && !in_array((int) $item, $action))
        {
            $action[] = (int) $item;
        }
    }
    if (count($action) <= 0)
    {
        echo CliHelper::colorText('行動標的（action：須為數字 0 - 6 並以逗號分隔）未正確指定，將從 7 種普通行動中隨機執行！', '#ffc080', true);
        $action = range(0, 6);
    }
}

# 輸出模式（預設為同步寫入檔案並顯示於終端機）
$syncOutput = isset($option['output']) ? true : false;

# 循環執行
while (true)
{
    $retry = 0;

    # 取得玩家基本資訊
    $result = MyKirito::getInstance()->getPersonalData($player);
    if ($result['httpStatusCode'] !== 200)
    {
        echo CliHelper::colorText("MyKirito::getPersonalData HTTP 狀態碼：{$result['httpStatusCode']}", '#ff8080', true);
        exit(1);
    }
    else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
    {
        echo CliHelper::colorText("MyKirito::getPersonalData 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", '#ff8080', true);
        exit(1);
    }
    $response = $result['response'];

    # 從玩家基本資訊中取出最後行動時間、最後領取樓層獎勵時間及當前所在樓層
    $lastAction = $response['lastAction'];
    $lastFloorBonus = $response['lastFloorBonus'] ?? null;
    $floor = $response['floor'];

    # 取得現在時間
    $now = Helper::Timestamp() * 1000;

    # 在指定的行動標的範圍內隨機執行
    if (($now - $lastAction) > (Constant::ActionCD + Constant::ActionCDBuffer))
    {
        $seed = mt_rand(0, count($action) - 1);
        $actionKey = $action[$seed];
        $actionAlias = Constant::NormalAction[$actionKey];

        # 在最大重試次數內，發送行動請求
        while ($retry < Constant::MaxRetry)
        {
            $result = MyKirito::getInstance()->doAction($player, $actionAlias);

            if ($result['httpStatusCode'] !== 200 || ($result['error']['code'] !== 0 || $result['error']['message'] !== ''))
            {
                $logTime = Helper::Time($now / 1000);

                if ($result['httpStatusCode'] !== 200)
                {
                    $logMessage = "[{$logTime}] MyKirito::doAction('{$player}', '{$actionAlias}') HTTP 狀態碼：{$result['httpStatusCode']}";
                    $detailLogMessage = "[{$logTime}] MyKirito::doAction('{$player}', '{$actionAlias}') \n" . json_encode($result, 448);
                }
                else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
                {
                    $logMessage = "[{$logTime}] MyKirito::doAction('{$player}', '{$actionAlias}') 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}";
                    $detailLogMessage = "[{$logTime}] MyKirito::doAction('{$player}', '{$actionAlias}') \n" . json_encode($result, 448);
                }

                file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                file_put_contents($detailLogFile, $detailLogMessage . PHP_EOL, FILE_APPEND);

                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, '#ff8080', true);
                }

                $now = Helper::Timestamp() * 1000;
                $retry++;

                # 每次重試間隔
                sleep(1);
            }
            else
            {

                $logTime = Helper::Time($now / 1000);
                $actionName = Constant::ActionName[$actionAlias];
                $logMessage = "[{$logTime}] {$actionName}";
                $detailLogMessage = "[{$logTime}] \n" . json_encode($result, 448);
        
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
    }

    # 領取樓層獎勵前間隔
    sleep(1);

    # 更新現在時間
    $now = Helper::Timestamp() * 1000;

    # 當前位於 1 層以上，且已超過領取獎勵冷卻時間時，發送領取樓層獎勵請求
    if ($floor > 0 && !is_null($lastFloorBonus) && ($now - $lastFloorBonus) > (Constant::FloorBonusCD + Constant::ActionCDBuffer))
    {
        $actionAlias = 'Bonus';

        # 在最大重試次數內，發送行動請求
        while ($retry < Constant::MaxRetry)
        {
            $result = MyKirito::getInstance()->doAction($player, $actionAlias);

            if ($result['httpStatusCode'] !== 200 || ($result['error']['code'] !== 0 || $result['error']['message'] !== ''))
            {
                $logTime = Helper::Time($now / 1000);

                if ($result['httpStatusCode'] !== 200)
                {
                    $logMessage = "[{$logTime}] MyKirito::doAction('{$player}', '{$actionAlias}') HTTP 狀態碼：{$result['httpStatusCode']}";
                    $detailLogMessage = "[{$logTime}] MyKirito::doAction('{$player}', '{$actionAlias}') \n" . json_encode($result, 448);
                }
                else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
                {
                    $logMessage = "[{$logTime}] MyKirito::doAction('{$player}', '{$actionAlias}') 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}";
                    $detailLogMessage = "[{$logTime}] MyKirito::doAction('{$player}', '{$actionAlias}') \n" . json_encode($result, 448);
                }

                file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
                file_put_contents($detailLogFile, $detailLogMessage . PHP_EOL, FILE_APPEND);

                if ($syncOutput)
                {
                    echo CliHelper::colorText($logMessage, '#ff8080', true);
                }

                $now = Helper::Timestamp() * 1000;
                $retry++;

                # 每次重試間隔
                sleep(1);
            }
            else
            {

                $logTime = Helper::Time($now / 1000);
                $actionName = Constant::ActionName[$actionAlias];
                $logMessage = "[{$logTime}] {$actionName}";
                $detailLogMessage = "[{$logTime}] \n" . json_encode($result, 448);
        
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
    }

    # 每次執行間隔
    sleep(70);
}