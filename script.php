<?php

chdir(__DIR__);
require_once './entrypoint.php';

use Lib\Helper;
use Lib\MyKiritoAPI;
use App\MyKirito;

$player = 'Taras';

# 玩家個人資料
// $result = MyKirito::getInstance()->getPersonalData($player);
// echo json_encode($result, 448);

# 更改個人狀態
// $status = '刷畢娜中｜300敗達成｜友切死了2次QQ｜打算入手小說';
// $result = MyKirito::getInstance()->updatePersonalStatus($player, $status);
// echo json_encode($result, 448);

# 取得玩家列表
// $result = MyKirito::getInstance()->getUserList($player, 68, 1);
// echo json_encode($result, 448);

# 搜尋指定暱稱的玩家（完全比對）
// $result = MyKirito::getInstance()->getPlayerByName($player, 'TSH');
// echo json_encode($result, 448);

# 搜尋指定 ID 的玩家
// $result = MyKirito::getInstance()->getPlayerById($player, '61d923745d8eb3992a01e7c3');
// echo json_encode($result, 448);

# 從暱稱搜尋玩家詳細資訊
// $result = MyKirito::getInstance()->getDetailByPlayerName($player, 'TSK');
// echo json_encode($result, 448);

# 挑戰對手（打架）
// $result = MyKirito::getInstance()->chellenge($player, 'Taras', 0, '測試對戰');
// echo json_encode($result, 448);

# 取得 BOSS 資料
// $result = MyKirito::getInstance()->getBoss($player);
// echo json_encode($result, 448);

# 挑戰 BOSS
// $result = MyKirito::getInstance()->challengeBoss($player);
// echo json_encode($result, 448);

# 成就
// $result = MyKirito::getInstance()->getAchievements($player);
// echo json_encode($result, 448);

# 成就榜
// $result = MyKirito::getInstance()->getAchievementRank($player);
// echo json_encode($result, 448);

# 名人堂
// $result = MyKirito::getInstance()->getHallOfFame($player);
// echo json_encode($result, 448);

# 已解鎖角色
// $result = MyKirito::getInstance()->getUnlockedCharacters($player);
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
// $result = MyKirito::getInstance()->reincarnation($player, $payload);
// echo json_encode($result, 448);

// # 查看防守戰報
// $result = MyKirito::getInstance()->getDefenseReports($player);
// echo json_encode($result, 448);

# 查看攻擊戰報
// $result = MyKirito::getInstance()->getAttackReports($player);
// echo json_encode($result, 448);

# 查看 BOSS 戰報
// $result = MyKirito::getInstance()->getBossReports($player);
// echo json_encode($result, 448);

# 查看詳細戰報
// $reportId = '61fcbd435d8eb3992a11c71f';
// $result = MyKirito::getInstance()->getDetailReport($reportId);
// echo json_encode($result, 448);

# 金手指解鎖角色
// $character = 'Klein';
// $result = MyKirito::getInstance()->unlockCharacterBySecret($player, $character);
// echo json_encode($result, 448);
