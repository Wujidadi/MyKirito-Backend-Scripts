<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use App\Constant;
use App\MyKirito;
use Lib\Helpers\CliHelper;
use Lib\Helpers\Helper;

# 由命令行參數指定玩家暱稱及輸出模式
$option = getopt('', ['player:', 'output']);

# 玩家暱稱
if (!isset($option['player']) || $option['player'] === '') {
    echo CliHelper::colorText('必須指定玩家暱稱（player）！', CLI_TEXT_ERROR, true);
    exit(CLI_ERROR);
}
$player = $option['player'];

# 玩家暱稱必須在 configs/Players.php 中有建檔
if (!in_array($player, array_keys(PLAYER))) {
    echo CliHelper::colorText('玩家暱稱尚未納入紀錄！', CLI_TEXT_ERROR, true);
    exit(CLI_ERROR);
}

# 宣告 MyKirito 物件實例
$myKirito = MyKirito::getInstance($player);

# 輸出模式（預設為僅寫入檔案，不顯示於終端機）
$writeToFile = !isset($option['output']);

try {
    # 取得玩家基本資訊
    $result = $myKirito->getPersonalData($player);
    if ($result['httpStatusCode'] !== 200) {
        echo CliHelper::colorText("MyKirito::getPersonalData HTTP 狀態碼：{$result['httpStatusCode']}", CLI_TEXT_ERROR, true);
        exit(CLI_ERROR);
    } elseif ($result['error']['code'] !== 0 || $result['error']['message'] !== '') {
        echo CliHelper::colorText("MyKirito::getPersonalData 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", CLI_TEXT_ERROR, true);
        exit(CLI_ERROR);
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
        '玻璃值'     => $response['murder'],
        '復活次數'   => $response['resurrect'],
        '進保護模式所需被殺次數'     => $response['murder'] * 5 - $response['defDeath'] + ($response['murder'] <= 0 ? 2 : 1),
        '最後一次更新個人狀態的時間' => @Helper::TimeDisplay($response['lastStatus'] / 1000),
        '最後一次行動的時間'         => @Helper::TimeDisplay($response['lastAction'] / 1000),
        '最後一次領取樓層獎勵的時間' => @Helper::TimeDisplay($response['lastFloorBonus'] / 1000),
        '最後一次打人的時間'         => @Helper::TimeDisplay($response['lastChallenge'] / 1000),
        '最後一次打Boss的時間'       => @Helper::TimeDisplay($response['lastBossChallenge'] / 1000),
        '通關時間'   => '',
        '記憶碎片'   => '',
        '總成就點數' => 0,
        '成就數'     => 0,
        '成就列表'   => [],
        '解鎖角色數' => 0,
        '已解鎖角色' => []
    ];

    # 以查詢一般玩家的方式，查詢玩家的通關時間、碎片等資訊
    $result = $myKirito->getDetailByPlayerName($player, $player);
    if ($result['httpStatusCode'] !== 200) {
        echo CliHelper::colorText("MyKirito::getDetailByPlayerName HTTP 狀態碼：{$result['httpStatusCode']}", CLI_TEXT_ERROR, true);
        exit(CLI_ERROR);
    } elseif ($result['error']['code'] !== 0 || $result['error']['message'] !== '') {
        echo CliHelper::colorText("MyKirito::getDetailByPlayerName 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", CLI_TEXT_ERROR, true);
        exit(CLI_ERROR);
    }
    $response = $result['response'];
    if (isset($response['cleared'])) {
        $data['通關時間'] = @Helper::TimeDisplay($response['cleared'] / 1000);
    }
    if (isset($response['fragment'])) {
        $data['碎片'] = $response['fragment'];
    }

    # 查詢玩家成就
    $result = $myKirito->getAchievements($player);
    if ($result['httpStatusCode'] !== 200) {
        echo CliHelper::colorText("MyKirito::getAchievements HTTP 狀態碼：{$result['httpStatusCode']}", CLI_TEXT_ERROR, true);
        exit(CLI_ERROR);
    } elseif ($result['error']['code'] !== 0 || $result['error']['message'] !== '') {
        echo CliHelper::colorText("MyKirito::getAchievements 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", CLI_TEXT_ERROR, true);
        exit(CLI_ERROR);
    }
    $response = $result['response'];
    $list = $response['list'];
    $achievements = [];
    $point = 0;
    foreach ($list as $achievement) {
        $point += $achievement['point'];
        $achievements[] = $achievement['name'] . '（' . $achievement['point'] . ' 點，累計 ' . $point . ' 點）';
    }
    $data['成就列表'] = $achievements;
    $data['成就數'] = count($achievements);
    $data['總成就點數'] = $point;

    # 查詢已解鎖角色
    $result = $myKirito->getUnlockedCharacters($player);
    if ($result['httpStatusCode'] !== 200) {
        echo CliHelper::colorText("MyKirito::getUnlockedCharacters HTTP 狀態碼：{$result['httpStatusCode']}", CLI_TEXT_ERROR, true);
        exit(CLI_ERROR);
    } elseif ($result['error']['code'] !== 0 || $result['error']['message'] !== '') {
        echo CliHelper::colorText("MyKirito::getUnlockedCharacters 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", CLI_TEXT_ERROR, true);
        exit(CLI_ERROR);
    }
    $response = $result['response'];
    $list = $response['unlockedCharacters'];
    $characters = [];
    foreach ($list as $character) {
        $characters[] = $character['character'] . '：' . $character['name'] . '（' . $character['title'] . '）';
    }
    $data['已解鎖角色'] = $characters;
    $data['解鎖角色數'] = count($characters);

    if ($writeToFile) {
        # 寫入 JSON 檔案
        $directory = STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . 'PersonalOverview';
        if (!is_dir($directory)) {
            mkdir($directory);
        }
        $file = $directory . DIRECTORY_SEPARATOR . $player . '_' . date('YmdHis') . '.json';
        file_put_contents($file, json_encode($data, 448));
    } else {
        # 命令行輸出
        echo CliHelper::colorText(json_encode($data, 448), CLI_TEXT_INFO, true);
    }
} catch (Throwable $ex) {
    # 不做事
}

unset($myKirito);
