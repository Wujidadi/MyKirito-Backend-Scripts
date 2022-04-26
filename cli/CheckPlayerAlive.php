<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use App\MyKirito;
use Lib\Helpers\CliHelper;
use Lib\Colors;

# 玩家暱稱
$player = 'TSK';

# 以玩家暱稱宣告物件實例
$myKirito = MyKirito::getInstance($player);

# 獲取待檢玩家列表
$playerList = require_once STORAGE_DIR . '/others/PlayerListToCheckAlive.php';

# 依待檢玩家數量定義行號前補上空格數
$strPadDigit = strlen(count($playerList));

# 逐一檢查存活狀態
foreach ($playerList as $i => $playerName)
{
    $playerInfo = $myKirito->getPlayerByName($playerName);
    if (is_array($playerInfo) && isset($playerInfo['response']) && is_array($playerInfo['response']))
    {
        extract($playerInfo['response']['userList'][0]);

        # 死亡及存活分別以紅色及綠色顯示
        if ($color === 'grey')
        {
            $mainColor = Colors::Red;
            $viceColor = Colors::Salmon;
        }
        else
        {
            $mainColor = Colors::Lime;
            $viceColor = Colors::PaleGreen;
        }

        # 同時列出玩家角色、等級與個人狀態
        $position = "{$floor} 層 {$lv} 等";
        $identity = "{$character}（{$title}）";

        # 行號（前補空格）
        $lineNumber = str_pad($i + 1, $strPadDigit, ' ', STR_PAD_LEFT);

        # 輸出
        echo $lineNumber . ' ' .
             CliHelper::colorText($playerName, $mainColor) .
             '【' . CliHelper::colorText($position, $viceColor) .
             CliHelper::colorText($identity, $viceColor, false, true) . '】' .
             '：' . $status . PHP_EOL;
    }

    usleep(640000);
}
