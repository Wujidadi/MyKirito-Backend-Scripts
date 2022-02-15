<?php

namespace Lib\Helpers;

/**
 * 輔助工具類別
 */
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
        return (is_null($Timestamp) || $Timestamp === 0 || $Timestamp === '') ? '' : self::Time($Timestamp);
    }

    /**
     * 判斷輸入的字串或數字是否整數
     *
     * @param  integer|double|string  $number  要判斷的數字或字串
     * @return boolean
     */
    public static function isInteger(mixed $number): bool
    {
        return is_numeric($number) && bcsub((float) $number, (int) $number, 16) === '0.0000000000000000';
    }

    /**
     * 將原生 `var_export` 函數的執行結果轉換為 PHP 5.4 以後的格式
     *
     * @param  mixed   $var     原始變數
     * @param  string  $indent  行首縮排字串
     * @return string
     */
    public static function varExport(mixed $var, string $indent = ''): string
    {
        switch (gettype($var))
        {
            case 'string':
                return '\'' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '\'';

            case 'array':
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value)
                {
                    $r[] = "{$indent}    "
                         . ($indexed ? '' : self::varExport($key) . ' => ')
                         . self::varExport($value, "{$indent}    ");
                }
                return "[\n" . implode(",\n", $r) . "\n{$indent}]";

            case 'object':
                $str = preg_replace(
                    [
                        '/\(object\) array\(/',
                        '/ => \n +/',
                        '/ => array \(\n/',
                        '/\),\n/',
                        '/\)$/',
                        '/,\n( *)\]/',
                        '/( *)\d+ => /'
                    ],
                    [
                        '(object) [',
                        ' => ',
                        " => [\n",
                        "],\n",
                        ']',
                        "\n$1]",
                        '$1'
                    ],
                    var_export($var, true)
                );
                $arr = explode(PHP_EOL, $str);
                for ($i = 0; $i < count($arr); $i++)
                {
                    $tmpStr = $arr[$i];
                    if (preg_match_all('/^ */', $arr[$i], $matches))
                    {
                        $tmpStr = preg_replace('/^ +/', '', $arr[$i]);
                        $indentLen = strlen($matches[0][0]);
                        $spaceLen = $indentLen % 2 ? ($indentLen - 1) * 2 : $indentLen * 2;
                        $tmpStr = str_repeat(' ', $spaceLen) . $tmpStr;
                        $arr[$i] = $tmpStr;
                    }
                }
                $str = implode("\n", $arr);
                return $str;

            case 'boolean':
                return $var ? 'TRUE' : 'FALSE';

            default:
                return var_export($var, true);
        }
    }
}
