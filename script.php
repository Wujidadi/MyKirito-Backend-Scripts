<?php

chdir(__DIR__);
require_once './entrypoint.php';

use Lib\Helpers\Helper;
use Lib\Helpers\CliHelper;
use Lib\Log\Logger;
use Lib\MyKiritoAPI;
use App\MyKirito;
use App\TelegramBot;

# 玩家暱稱
$player = 'Taras';

# 以玩家暱稱宣告物件實例
$myKirito = MyKirito::getInstance($player);

# 玩家個人資料
// $result = $myKirito->getPersonalData();
// echo json_encode($result, 448);

# 更改個人狀態
// $status = '私人刷勝場包｜橘名是渡本帳拿角色';
// $result = $myKirito->updatePersonalStatus($status);
// echo json_encode($result, 448);

# 行動
// $action = 'Girl';
// $result = $myKirito->doAction($action);
// if (isset($result['response']['myKirito']['unlockedCharacters']))
// {
//     $last = count($result['response']['myKirito']['unlockedCharacters']) - 1;
//     $newestUnlockedCharacter = $result['response']['myKirito']['unlockedCharacters'][$last];
//     $characterName = $newestUnlockedCharacter['name'];
//     $message = "解鎖角色：{$characterName}";
//     echo CliHelper::colorText($message, '#ffd700', true);
// }
// else
// {
//     echo CliHelper::colorText('沒有解鎖角色！', '#efb7b7', true);
// }
// echo json_encode($result, 448);

# 取得玩家列表
// $result = $myKirito->getUserList(70, 600);
// echo json_encode($result, 448);

# 搜尋指定暱稱的玩家（完全比對）
// $result = $myKirito->getPlayerByName('TSH');
// echo json_encode($result, 448);

# 搜尋指定 ID 的玩家
// $result = $myKirito->getPlayerById('61d923745d8eb3992a01e7c3');
// echo json_encode($result, 448);

# 從暱稱搜尋玩家詳細資訊
// $result = $myKirito->getDetailByPlayerName('TSK');
// echo json_encode($result, 448);

# 挑戰對手（打架）
// $result = $myKirito->challenge('Taras', 0, '測試對戰');
// echo json_encode($result, 448);

# 取得 BOSS 資料
// $result = $myKirito->getBoss();
// echo json_encode($result, 448);

# 挑戰 BOSS
// $result = $myKirito->challengeBoss();
// echo json_encode($result, 448);

# 成就
// $result = $myKirito->getAchievements();
// echo json_encode($result, 448);

# 成就榜
// $result = $myKirito->getAchievementRank();
// echo json_encode($result, 448);

# 名人堂
// $result = $myKirito->getHallOfFame();
// // echo json_encode($result, 448);
// $output = '';
// foreach ($result['response']['userList'] as $user)
// {
//     $output .= "{$user['uid']}\t{$user['avatar']}\t{$user['character']}\n";
// }
// echo $output;

# 已解鎖角色
// $result = $myKirito->getUnlockedCharacters();
// echo json_encode($result, 448);

# 轉生
// $payload = [
//     'character' => 'honda',
//     'rattrs' => [
//         'hp' => 10,
//         'atk' => 10,
//         'def' => 10,
//         'stm' => 10,
//         'agi' => 10,
//         'spd' => 10,
//         'tec' => 10,
//         'int' => 10,
//         'lck' => 10
//     ],
//     'useReset' => false,
//     'useResetBoss' => false
// ];
// $result = $myKirito->reincarnation(, $payload);
// echo json_encode($result, 448);

// # 查看防守戰報
// $result = $myKirito->getDefenseReports();
// echo json_encode($result, 448);

# 查看攻擊戰報
// $result = $myKirito->getAttackReports();
// echo json_encode($result, 448);

# 查看 BOSS 戰報
// $result = $myKirito->getBossReports();
// echo json_encode($result, 448);

# 查看詳細戰報
// $reportId = '61fcbd435d8eb3992a11c71f';
// $result = MyKirito::getDetailReport($reportId);
// echo json_encode($result, 448);

# 金手指解鎖角色
// $character = 'Klein';
// $result = $myKirito->unlockCharacterBySecret($character);
// echo json_encode($result, 448);

# 發送 Telegram 通知
// $message = 'System Alert Again and Again';
// $result = TelegramBot::getInstance()->sendMessage($message);
// echo json_encode($result, 448);

# 寫日誌
// $logMessage = 'This is a general log';
// // $logMessage = [
// //     'brief' => 'This is a brief log',
// //     'detail' => 'This is a detail log'
// // ];
// $logFile = [
//     'brief' => LOG_DIR . DIRECTORY_SEPARATOR . 'Test.log',
//     'detail' => LOG_DIR . DIRECTORY_SEPARATOR . 'Test_2.log',
// ];
// $logTime = Helper::Time(Helper::Timestamp() - 1800);
// Logger::getInstance()->log($logMessage, $logFile, false, $logTime);

unset($myKirito);

exit(0);
