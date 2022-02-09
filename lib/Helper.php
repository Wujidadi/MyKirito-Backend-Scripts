<?php

namespace Lib;

class Helper
{
    /**
     * 返回微秒級時間字串；有指定時間戳時，返回時間戳對應的時間字串
     *
     * @param  string|integer|double|null  $Timestamp  時間戳
     * @return string
     */
    public static function Time($Timestamp = null)
    {
        if ($Timestamp !== null)
        {
            $Timestamp = (string) $Timestamp;
            $date = explode('.', $Timestamp);
            $s = (int) $date[0];
            $ms = isset($date[1]) ? rtrim($date[1], '0') : '0';
            $time = ($ms != 0) ? date('Y-m-d H:i:s', $s) . '.' . $ms : date('Y-m-d H:i:s', $s);
            return $time;
        }
        else
        {
            $datetime = new \DateTime();
            $time = $datetime->format('Y-m-d H:i:s.u');
            return $time;
        }
    }

    /**
     * 返回當下的微秒級時間戳；有指定時間字串時，返回該字串的時間戳
     *
     * @param  string|null  $TimeString  時間字串
     * @return double
     */
    public static function Timestamp($TimeString = null)
    {
        if ($TimeString !== null)
        {
            $time = explode('+', $TimeString);
            $time = explode('.', $time[0]);
            $s = strtotime($time[0]);
            $ms = isset($time[1]) ? rtrim($time[1], '0') : '0';
            $mtime = ($ms != 0) ? ($s . '.' . $ms) : $s;
        }
        else
        {
            $time = explode(' ', microtime());
            $s = $time[1];
            $ms = rtrim($time[0], '0');
            $ms = preg_replace('/^0/', '', $ms);
            $mtime = $s . $ms;
        }
        return (float) $mtime;
    }

    /**
     * 基於 `self::Time`，調整輸出顯示的時間
     *
     * @param  string|integer|double|null  $Timestamp  時間戳
     * @return string
     */
    public static function TimeDisplay($Timestamp = null)
    {
        if (is_null($Timestamp) || $Timestamp === 0 || $Timestamp === '')
        {
            return '';
        }
        else
        {
            return self::Time($Timestamp);
        }
    }

    public static function isInteger(mixed $number): bool
    {
        if (!is_numeric($number))
        {
            return false;
        }
        if (bcsub((float) $number, (int) $number, 16) !== '0.0000000000000000')
        {
            return false;
        }
        return true;
    }
}
