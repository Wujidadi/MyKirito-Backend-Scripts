<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use Lib\Helpers\Helper;
use Lib\Helpers\CliHelper;
use Lib\Log\Logger;
use Lib\FileLock;
use App\Constant;
use App\MyKirito;

#========== 起點 ==========#

# 腳本名稱（含副檔名）
$scriptName = basename(__FILE__);

# 腳本名稱（不含副檔名）
$cliName = basename(__FILE__, '.php');

# 由命令行參數指定玩家暱稱、轉生配置及輸出模式
$option = getopt('', ['player:', 'set:', 'output']);

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

# 轉生配置（必須為正確的 JSON 字串）
if (!isset($option['set']) || !($set = json_decode($option['set'], true)))
{
    echo CliHelper::colorText('必須以 JSON 指定轉生配置（set）！', CLI_TEXT_ERROR, true);
    exit(CLI_ERROR);
}

$character = $set['character'] ?? '';
if (!trim($character))
{
    echo CliHelper::colorText('轉生角色（set.character）設定不正確！', CLI_TEXT_ERROR, true);
    exit(CLI_ERROR);
}
$characterName = Constant::Character[$character];

# 輸出模式（預設為僅寫入檔案，不顯示於終端機）
$syncOutput = isset($option['output']);

# 加檔案鎖防止程序重複執行
FileLock::getInstance()->lock($fileLockName, $cliName);

#========== 執行起點 ==========#

try
{
    # 重試次數
    $retry = 0;

    # 執行上下文
    $context = "MyKirito::reincarnation";

    # 在最大重試次數內，發送轉生請求
    while ($retry < Constant::MaxRetry)
    {
        $result = $myKirito->reincarnation($set);

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

            $logMessage = [
                'brief' => "轉生成功，角色：{$characterName}",
                'detail' => $jsonResult
            ];

            Logger::getInstance()->log($logMessage, $logFiles, true, $logTime);

            if ($syncOutput)
            {
                echo CliHelper::colorText($logMessage['brief'], CLI_TEXT_INFO, true);
            }

            $exitStatus = CLI_OK;
            goto Endpoint;
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

        $exitStatus = CLI_ERROR;
        goto Endpoint;
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

    $exitStatus = CLI_ERROR;
    goto Endpoint;
}

#========== 終點 ==========#

Endpoint:

FileLock::getInstance()->unlock();

unset($myKirito);

exit($exitStatus);
