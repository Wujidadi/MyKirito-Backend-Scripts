<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use App\MyKirito;

# 玩家暱稱
$player = 'TSK';

# 以玩家暱稱宣告物件實例
$myKirito = MyKirito::getInstance($player);

$lv = 70;
$initialPage = 1;
$page = 1;
$usersInPage = 0;
$output = implode("\t", [
    '排名',
    'ID',
    '暱稱',
    '等級',
    '頭像',
    '角色（稱號）',
    '樓層',
    '個人狀態',
    '顏色'
]) . PHP_EOL;
$filePutFlag = 0;
while (true)
{
    $result = $myKirito->getUserList($lv, $page);
    $users = $result['response']['userList'];
    $usersInPage = count($users);

    if ($usersInPage <= 0)
    {
        break;
    }

    if ($lv === 70 && $initialPage === 1)
    {
        $rankShift = 0;    // 置頂有紀算第 0 名
    }
    else
    {
        $rankShift = 1;
    }

    $startIndex = ($page - 1) * 25 + $rankShift;    
    $endIndex = ($page - 1) * 25 + $usersInPage - (1 - $rankShift);

    $rank = $startIndex;

    foreach ($users as $user)
    {
        @$output .= implode("\t", [
            $rank++,
            $user['uid'],
            $user['nickname'],
            $user['lv'],
            $user['avatar'],
            "{$user['character']}（{$user['title']}）",
            $user['floor'],
            $user['status'],
            $user['color']
        ]) . PHP_EOL;
    }

    file_put_contents(STORAGE_DIR . '/AllUsers.tsv', $output, $filePutFlag);

    echo "進度：70 等以下玩家，已取得第 {$page} 頁（經驗值第 {$startIndex} - {$endIndex} 名玩家）\n";

    $page++;
    if ($page > $initialPage)
    {
        $output = '';
        $filePutFlag = FILE_APPEND;
    }

    usleep(1200000);

    // if ($page > 10) break;
}
