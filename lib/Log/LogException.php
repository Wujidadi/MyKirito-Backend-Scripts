<?php

namespace Lib\Log;

use Exception;

/**
 * 日誌例外類別
 */
class LogException extends Exception
{
    protected $message;
    protected $code;

    public function __construct($message = null, $code = 0)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
