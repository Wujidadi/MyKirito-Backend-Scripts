<?php

chdir(__DIR__);
require_once './entrypoint.php';

use App\MyKirito;

$player = 'TSK';

# 玩家個人資料
$result = MyKirito::getInstance()->getPersonalData($player);
echo json_encode($result, 448);

# 更改個人狀態
// $status = '刷畢娜中｜300敗達成｜友切死了2次QQ｜打算入手小說';
// $result = MyKirito::getInstance()->updatePersonalStatus($player, $status);
// echo json_encode($result, 448);

# 成就
// $result = MyKirito::getInstance()->getAchievements($player);
// echo json_encode($result, 448);

# 名人堂
// $result = MyKirito::getInstance()->getHallOfFame($player);
// echo json_encode($result, 448);
