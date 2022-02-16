<?php

namespace Lib\Log;

use Lib\Helpers\Helper;
use Lib\Log\LogException;

/**
 * 日誌類別
 */
class Logger
{

    /**
     * 物件單一實例
     *
     * @var self|null
     */
    protected static $_uniqueInstance = null;

    /**
     * 取得物件實例
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$_uniqueInstance === null) self::$_uniqueInstance = new self;
        return self::$_uniqueInstance;
    }

    /**
     * 建構子
     *
     * @return void
     */
    protected function __construct()
    {
        //
    }

    /**
     * 寫入日誌
     *
     * @param  array|string                $logMessage     日誌內容  
     *                                                     若為陣列型態，須具備 `brief` 及 `detail` 兩個 key（區分詳略）  
     *                                                     `$diffDetailLog` 為 `false` 時，不可設為陣列
     * @param  array|string                $logFile        日誌檔案  
     *                                                     若為陣列型態，須具備 `brief` 及 `detail` 兩個 key（區分詳略）
     * @param  boolean                     $diffDetailLog  日誌內容是否區分詳略  
     *                                                     預設值為 `false`  
     *                                                     為 `true` 時，日誌內容及檔案須區分詳略才有作用，但不區分也不會報錯，而是寫入相同的日誌內容  
     *                                                     為 `false` 時，日誌內容若有區分詳略，會報 `LogException` 錯誤
     * @param  string|integer|double|null  $logTime        日誌時間，忽略時自動取當下的時間
     * @return void
     */
    public function log(mixed $logMessage, mixed $logFile, bool $diffDetailLog = false, mixed $logTime = null): void
    {
        # 日誌時間
        if (is_null($logTime))
        {
            $logTime = Helper::Time();
        }
        else
        {
            if (Helper::IsInteger($logTime))
            {
                $logTime = Helper::Time($logTime);
            }
            else
            {
                if (!preg_match('/\d{4}[-\/]\d{1,2}[-\/]\d{1,2} \d{1,2}:\d{2}:\d{2}(?:\.\d{0,6})*/', $logTime))
                {
                    $logTime = Helper::Time(strtotime($logTime));
                }
            }
        }

        # 日誌內容為單一字串
        if ((!is_array($logMessage) && is_string($logMessage)))
        {
            $logData = "[{$logTime}] {$logMessage}";

            # 日誌檔案為單一字串，只存一份日誌
            if (!is_array($logFile) && is_string($logFile))
            {
                file_put_contents($logFile, $logData . PHP_EOL, FILE_APPEND);
            }
            # 日誌檔案為陣列，儲存多份日誌
            else
            {
                foreach ($logFile as $file)
                {
                    file_put_contents($file, $logData . PHP_EOL, FILE_APPEND);
                }
            }
        }
        # 日誌內容為陣列，則區分詳略
        else
        {
            # 日誌檔案也必須為陣列，且日誌內容與檔案均具備 brief（簡要）和 detail（詳細）兩個 key
            if (isset($logMessage['brief']) && isset($logMessage['detail']) &&
                isset($logFile['brief']) && isset($logFile['detail']))
            {
                # 詳略日誌分別寫入不同檔案
                if ($diffDetailLog)
                {
                    $logData = "[{$logTime}] {$logMessage['brief']}";
                    file_put_contents($logFile['brief'], $logData . PHP_EOL, FILE_APPEND);

                    $logData = "[{$logTime}] {$logMessage['detail']}";
                    file_put_contents($logFile['detail'], $logData . PHP_EOL, FILE_APPEND);
                }
                else
                {
                    throw new LogException('Both brief and detail log messages assigned but DiffDetailLog flag is FALSE', 1);
                }
            }
            else
            {
                throw new LogException('Key "brief" and "detail" must both exist in log message/file parameters in ARRAY type', 2);
            }
        }
    }
}
