<?php

namespace Lib\Helpers;

use Lib\Helpers\Helper;
use Lib\Log\Logger;

/**
 * 命令行輔助工具類別
 */
class CliHelper
{
    /**
     * 格式化命令行輸出
     *
     * @param  string   $text       輸出字串
     * @param  string   $hexColor   `#RRGGBB` 格式色碼
     * @param  boolean  $breakLine  最後是否換行，預設 `false`
     * @param  boolean  $underline  字元是否帶底線，預設 `false`
     * @return string               ANSI 格式化輸出字元
     */
    public static function colorText(string $text, string $hexColor = '', bool $breakLine = false, bool $underline = false): string
    {
        $eot = $breakLine ? PHP_EOL : '';
        $udl = $underline ? ';4' : '';

        if ($hexColor === '' || is_null($hexColor))
        {
            return "{$text}{$eot}";
        }
        else
        {
            list($r, $g, $b) = sscanf($hexColor, '#%02X%02X%02X');
            return "\033[38;2;{$r};{$g};{$b}{$udl}m{$text}\033[0m{$eot}";
        }
    }

    /**
     * 記錄錯誤日誌（區分簡要、詳細日誌）
     *
     * @param  array     $result      請求回應結果
     * @param  string[]  $logFiles    日誌檔案路徑
     * @param  string    $context     上下文，執行請求的方法名稱及參數，可忽略（預設為空字串）
     * @param  boolean   $syncOutput  是否同步輸出於終端機，可忽略（預設為 `false`）
     * @return void
     */
    public static function logError(array $result, array $logFiles, string $context = '', bool $syncOutput = false): void
    {
        # 日誌時間
        $logTime = Helper::Time();

        # 壓縮回應結果為 JSON
        $jsonResult = json_encode($result, 320);

        # 日誌上下文
        if ($context !== '') $context = "{$context} ";

        # 簡要日誌
        $logMessage['brief'] = $context;
        if ($result['httpStatusCode'] !== 200)
        {
            $logMessage['brief'] = "{$logMessage['brief']}HTTP 狀態碼：{$result['httpStatusCode']}，";
        }
        if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
        {
            $logMessage['brief'] = "{$logMessage['brief']}連線錯誤：({$result['error']['code']}) {$result['error']['message']}，";
        }
        else if (isset($result['response']['error']))
        {
            $logMessage['brief'] = "{$logMessage['brief']}系統錯誤：{$result['response']['error']}";
        }
        $logMessage['brief'] = preg_replace('/，$/', '', $logMessage['brief']);

        # 詳細日誌
        $logMessage['detail'] = "{$context}Response: {$jsonResult}";

        # 記錄日誌
        Logger::getInstance()->log($logMessage, $logFiles, true, $logTime);

        # 輸出於終端機
        if ($syncOutput)
        {
            echo CliHelper::colorText($logMessage['brief'], CLI_TEXT_ERROR, true);
        }
    }
}
