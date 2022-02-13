<?php

namespace Lib;

use Lib\Helper;

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
     * @param  array    $result         請求回應結果
     * @param  string   $function       執行請求的方法名稱及參數
     * @param  string   $logFile        簡要日誌檔案路徑
     * @param  string   $detailLogFile  詳細日誌檔案路徑
     * @param  boolean  $syncOutput     是否同步輸出於終端機
     * @return void
     */
    public static function logError(array $result, string $function, string $logFile, string $detailLogFile, bool $syncOutput): void
    {
        $logTime = Helper::Time();

        $logMessage = "[{$logTime}] {$function} ";
        if ($result['httpStatusCode'] !== 200)
        {
            $logMessage = "{$logMessage}HTTP 狀態碼：{$result['httpStatusCode']}，";
        }
        if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
        {
            $logMessage = "{$logMessage}連線錯誤：({$result['error']['code']}) {$result['error']['message']}，";
        }
        else if (isset($result['response']['error']))
        {
            $logMessage = "{$logMessage}系統錯誤：{$result['response']['error']}";
        }
        $logMessage = preg_replace('/，$/', '', $logMessage);

        $detailLogMessage = "[{$logTime}] {$function} Response: " . json_encode($result, 320);

        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
        file_put_contents($detailLogFile, $detailLogMessage . PHP_EOL, FILE_APPEND);

        if ($syncOutput)
        {
            echo CliHelper::colorText($logMessage, '#ff8080', true);
        }
    }

    /**
     * 從請求回應結果中取出最新解鎖的角色
     *
     * @param  array  $result  請求回應結果
     * @return array           最新解鎖的角色資訊，包括 `character`、`name` 及 `avatar` 3 個部分
     */
    public static function getNewestUnlockedCharacter(array $result): array
    {
        $last = count($result['response']['myKirito']['unlockedCharacters']) - 1;
        return $result['response']['myKirito']['unlockedCharacters'][$last];
    }

    /**
     * 建構 Telegram 自動通知訊息
     *
     * @param [type] $command
     * @param [type] $errorMessage
     * @return string
     */
    /**
     * 建構 Telegram 通知訊息
     *
     * @param  string       $title    訊息標題（首段）
     * @param  string       $command  執行的命令
     * @param  string       $message  要通知的訊息
     * @param  string       $type     訊息類型，預設值為 `error`
     * @param  string|null  $time     訊息時間
     * @return string
     */
    public static function buildNotificationMessage(string $title, string $command, string $message, string $type = 'error', ?string $time = null): string
    {
        $type = strtolower($type);
        switch ($type)
        {
            case 'general':
            case 'normal':
                $typeText = '訊息';
                break;

            case 'abnormal':
                $typeText = '異常';
                break;

            case 'error':
            default:
                $typeText = '錯誤';
                break;
        }

        if (is_null($time))
        {
            $time = Helper::Time();
        }

        return <<<TEXT
        {$title}

        命令：`{$command}`
        {$typeText}：`{$message}`
        時間：`{$time}`
        TEXT;
    }

    /**
     * 壓縮 Telegram 通知訊息以便記錄於日誌
     *
     * @param  string  $notificationMessage  通知訊息
     * @return string
     */
    public static function buildNotificationLogMessage(string $notificationMessage): string
    {
        return preg_replace(
            [
                '/\n+/',
                '/`/',
                '/，時間：.+$/'
            ],
            [
                '，',
                '',
                ''
            ],
            $notificationMessage
        );
    }
}
