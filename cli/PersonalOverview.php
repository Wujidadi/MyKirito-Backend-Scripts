<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use Lib\Helper;
use Lib\CliHelper;
use App\Constant;
use App\MyKirito;

# 由命令行參數指定玩家暱稱及輸出模式
$option = getopt('', ['player:', 'output']);
if (!isset($option['player']) || $option['player'] === '')
{
    echo CliHelper::colorText('必須指定玩家暱稱（player）！', '#ff8080', true);
    exit(1);
}

# 玩家暱稱
$player = $option['player'];

# 輸出模式（預設為寫入檔案）
$writeToFile = isset($option['output']) ? false : true;

# 玩家暱稱必須在 configs/IdTokens.php 中有建檔
if (!in_array($player, array_keys(PLAYER)))
{
    echo CliHelper::colorText('玩家暱稱尚未納入紀錄！', '#ff8080', true);
    exit(1);
}

# 取得玩家基本資訊
$result = MyKirito::getInstance()->getPersonalData($player);
if ($result['httpStatusCode'] !== 200)
{
    echo CliHelper::colorText("MyKirito::getPersonalData HTTP 狀態碼：{$result['httpStatusCode']}", '#ff8080', true);
    exit(1);
}
else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
{
    echo CliHelper::colorText("MyKirito::getPersonalData 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", '#ff8080', true);
    exit(1);
}
$response = $result['response'];

$data = [
    'ID'         => $response['_id'],
    '暱稱'       => $response['nickname'],
    '角色'       => $response['character'],
    '稱號'       => $response['title'],
    '狀態'       => $response['status'],
    '顏色'       => Constant::Color[$response['color']],
    '是否死亡'   => $response['dead'] ? '是' : '否',
    '樓層'       => $response['floor'],
    '等級'       => $response['lv'],
    '經驗值'     => $response['exp'],
    'HP'         => ($response['hp']  + $response['rattrs']['hp'] * 10) . ' (+' . $response['rattrs']['hp']  * 10 . ')',
    '攻擊'       => ($response['atk'] + $response['rattrs']['atk'])     . ' (+' . $response['rattrs']['atk'] . ')',
    '防禦'       => ($response['def'] + $response['rattrs']['def'])     . ' (+' . $response['rattrs']['def'] . ')',
    '體力'       => ($response['stm'] + $response['rattrs']['stm'])     . ' (+' . $response['rattrs']['stm'] . ')',
    '敏捷'       => ($response['agi'] + $response['rattrs']['agi'])     . ' (+' . $response['rattrs']['agi'] . ')',
    '反應速度'   => ($response['spd'] + $response['rattrs']['spd'])     . ' (+' . $response['rattrs']['spd'] . ')',
    '技巧'       => ($response['tec'] + $response['rattrs']['tec'])     . ' (+' . $response['rattrs']['tec'] . ')',
    '智力'       => ($response['int'] + $response['rattrs']['int'])     . ' (+' . $response['rattrs']['int'] . ')',
    '幸運'       => ($response['lck'] + $response['rattrs']['lck'])     . ' (+' . $response['rattrs']['lck'] . ')',
    '主動擊殺'   => $response['kill'],
    '防衛擊殺'   => $response['defKill'],
    '總主動擊殺' => $response['totalKill'],
    '總防衛擊殺' => $response['totalDefKill'],
    '遭襲死亡'   => $response['defDeath'],
    '遭反殺死亡' => $response['totalDeath'],
    '勝場'       => $response['win'],
    '敗場'       => $response['lose'],
    '總勝場'     => $response['totalWin'],
    '總敗場'     => $response['totalLose'],
    '轉生次數'   => $response['reincarnation'],
    '洗白點數'   => $response['reset'],
    '總行動次數' => $response['actionCount'],
    '總挑戰次數' => $response['challengeCount'],
    '謀殺次數'   => $response['murder'],
    '復活次數'   => $response['resurrect'],
    '最後一次更新個人狀態的時間' => @Helper::TimeDisplay($response['lastStatus'] / 1000),
    '最後一次行動的時間'         => @Helper::TimeDisplay($response['lastAction'] / 1000),
    '最後一次領取樓層獎勵的時間' => @Helper::TimeDisplay($response['lastFloorBonus'] / 1000),
    '最後一次打人的時間'         => @Helper::TimeDisplay($response['lastChallenge'] / 1000),
    '最後一次打Boss的時間'       => @Helper::TimeDisplay($response['lastBossChallenge'] / 1000),
    '通關時間'                   => '',
    '碎片'                       => '',
    '總成就點數' => $response['achievementPoints'],
    '成就列表'   => [],
    '已解鎖角色' => []
];

# 以查詢一般玩家的方式，查詢玩家的通關時間、碎片等資訊
$result = MyKirito::getInstance()->getDetailByPlayerName($player, $player);
if ($result['httpStatusCode'] !== 200)
{
    echo CliHelper::colorText("MyKirito::getDetailByPlayerName HTTP 狀態碼：{$result['httpStatusCode']}", '#ff8080', true);
    exit(1);
}
else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
{
    echo CliHelper::colorText("MyKirito::getDetailByPlayerName 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", '#ff8080', true);
    exit(1);
}
$response = $result['response'];
if (isset($response['cleared']))
{
    $data['通關時間'] = @Helper::TimeDisplay($response['cleared'] / 1000);
}
if (isset($response['fragment']))
{
    $data['碎片'] = $response['fragment'];
}

# 查詢玩家成就
$result = MyKirito::getInstance()->getAchievements($player);
if ($result['httpStatusCode'] !== 200)
{
    echo CliHelper::colorText("MyKirito::getAchievements HTTP 狀態碼：{$result['httpStatusCode']}", '#ff8080', true);
    exit(1);
}
else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
{
    echo CliHelper::colorText("MyKirito::getAchievements 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", '#ff8080', true);
    exit(1);
}
$response = $result['response'];
$list = $response['list'];
$achievements = [];
$point = 0;
foreach ($list as $achievement)
{
    $point += $achievement['point'];
    $achievements[] = $achievement['name'] . '（' . $achievement['point'] . ' 點，累計 ' . $point . ' 點）';
}
$data['成就列表'] = $achievements;

# 查詢已解鎖角色
$result = MyKirito::getInstance()->getUnlockedCharacters($player);
if ($result['httpStatusCode'] !== 200)
{
    echo CliHelper::colorText("MyKirito::getUnlockedCharacters HTTP 狀態碼：{$result['httpStatusCode']}", '#ff8080', true);
    exit(1);
}
else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
{
    echo CliHelper::colorText("MyKirito::getUnlockedCharacters 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", '#ff8080', true);
    exit(1);
}
$response = $result['response'];
$list = $response['unlockedCharacters'];
$characters = [];
foreach ($list as $character)
{
    $characters[] = $character['character'] . '：' . $character['name'] . '（' . $character['title'] . '）';
}
$data['已解鎖角色'] = $characters;

if ($writeToFile)
{
    # 寫入 JSON 檔案
    $directory = STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . 'PersonalOverview';
    if (!is_dir($directory)) mkdir($directory);
    $file = $directory . DIRECTORY_SEPARATOR . $player . '.json';
    file_put_contents($file, json_encode($data, 448));
}
else
{
    # 命令行輸出
    echo CliHelper::colorText(json_encode($data, 448), '#aaffff', true);
}
