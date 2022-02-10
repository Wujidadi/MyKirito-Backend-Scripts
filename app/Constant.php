<?php

namespace App;

/**
 * 通用常數類別
 */
class Constant
{
    /**
     * 請求發送失敗或出錯時，最多重試的次數
     *
     * @var integer
     */
    const MaxRetry = 5;

    /**
     * 失敗重試間隔（秒）
     *
     * @var integer
     */
    const RetryInterval = 1;

    /**
     * 冷卻時間緩衝（毫秒），避免本地與伺服器時間誤差
     *
     * @var integer
     */
    const CooldownBuffer = 3000;

    /**
     * 一般行動冷卻時間（毫秒）
     *
     * @var integer
     */
    const ActionCD = 66000;

    /**
     * 領取樓層獎勵冷卻時間（毫秒）
     *
     * @var integer
     */
    const FloorBonusCD = 14400000;

    /**
     * 嘗試行動與領取樓層獎勵之間的時間間隔（秒）
     *
     * @var integer
     */
    const ActionBonusInterval = 1;

    /**
     * 行動及領取樓層獎勵檢查循環執行的時間間隔（秒）
     *
     * @var integer
     */
    const ActionRoundInterval = 70;

    /**
     * 挑戰冷卻時間（毫秒）
     *
     * @var integer
     */
    const ChallengeCD = 173000;

    /**
     * 每次挑戰的時間間隔（秒）
     *
     * @var integer
     */
    const ChallengeInterval = 180;

    /**
     * 一般行動列表（不含修行和領取樓層獎勵），與 `MyKirito::ACTION` 中對應項的 key 一致
     *
     * @var string[]
     */
    const NormalAction = [
        'Hunt',
        'Train',
        'Eat',
        'Girl',
        'Good',
        'Sit',
        'Fish'
    ];

    /**
     * 行動名稱列表
     *
     * @var string[]
     */
    const ActionName = [
        'Bonus' => '領取樓層獎勵',
        'Hunt'  => '狩獵兔肉',
        'Train' => '自主訓練',
        'Eat'   => '外出野餐',
        'Girl'  => '汁妹',
        'Good'  => '做善事',
        'Sit'   => '坐下休息',
        'Fish'  => '釣魚',
        '1h'    => '修行1小時',
        '2h'    => '修行2小時',
        '4h'    => '修行4小時',
        '8h'    => '修行8小時'
    ];

    /**
     * 玩家犯罪等級
     *
     * @var string[]
     */
    const Color = [
        'black'  => '黑名玩家',
        'orange' => '橘名玩家',
        'red'    => '紅名玩家'
    ];
}
