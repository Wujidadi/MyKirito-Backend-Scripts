<?php

namespace Lib\Helpers;

use Lib\Helpers\Helper;

/**
 * 通知輔助工具類別
 */
class NotificationHelper
{
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
