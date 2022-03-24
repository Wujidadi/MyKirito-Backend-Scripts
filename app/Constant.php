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
     * 修行列表，與 `MyKirito::ACTION` 中對應項的 key 一致
     *
     * @var string[]
     */
    const PracticeAction = [
        '1h',
        '2h',
        '4h',
        '8h'
    ];

    /**
     * 可自動化行動列表（不含領取樓層獎勵）  
     * 為 `self::NormalAction` 及 `self::PracticeAction` 的總和，且與 `MyKirito::ACTION` 中對應項的 key 一致
     */
    const AutoableAction = [
        '0' => 'Hunt',
        '1' => 'Train',
        '2' => 'Eat',
        '3' => 'Girl',
        '4' => 'Good',
        '5' => 'Sit',
        '6' => 'Fish',
        '1h' => '1h',
        '2h' => '2h',
        '4h' => '4h',
        '8h' => '8h'
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
        'black'  => '黑名玩家（良民）',
        'orange' => '橘名玩家（傷害前科）',
        'red'    => '紅名玩家（殺人犯）'
    ];

    /**
     * 角色 ID 與名稱對照表
     *
     * 從 profile API 取得的 avatar，當稱號有變化時，有些會變得與初始稱號時的 avatar 不同  
     * 可見 avatar 與角色 ID 不能直接對應，應該就只是用來存取角色 webp 頭像而已
     *
     * @var string[]
     */
    const Character = [
        'agil'               => '艾基爾',
        // 'agil2'              => '艾基爾',         # 艾基爾（頂裝道具店老闆）
        'aizen'              => '藍染惣右介',
        'alice'              => '愛麗絲',
        'aloAsuna'           => 'ALO亞絲娜',
        'aloKirito'          => 'ALO桐人',
        'asuna'              => '亞絲娜',
        'blackRabbit'        => '黑色雜燴兔',
        'blueRecon'          => '色違雷根',
        'bradley'            => '金·布拉德雷',
        // 'bradley2'           => '金·布拉德雷',    # 金·布拉德雷（人造人「憤怒」）
        'busuna'             => '聖誕巴士',
        'charizardX'         => '超級噴火龍X',
        'connie'             => '鎖鏈的康妮',
        'diavel'             => '提亞貝魯',
        'eugene'             => '尤金',
        'eugeo'              => '尤吉歐',
        'fish'               => '努西',
        'geodude'            => '星爆小拳石',
        'godfree'            => '哥德夫利',
        'gorilla'            => '猩爆戰象',
        'graveler'           => '星爆隆隆石',
        'guanYu'             => '關羽',
        // 'guanYuDevil'        => '關羽',           # 關羽（魔）
        'hatsumi'            => '初見泉',
        'hina'               => '天野陽菜',
        'hinamori'           => '雛森桃',
        'hitsugaya'          => '日番谷冬獅郎',
        'honda'              => '本多忠勝',
        'kibaou'             => '牙王',
        'kirito'             => '桐人',
        // 'kirito2'            => '桐人',           # 桐人（血盟騎士團成員）
        // 'kirito3'            => '桐人',           # 桐人（就憑你這菜B8 笑死）
        'kirito74'           => '難道只能使出那一招了嗎',
        'klein'              => '克萊因',
        'kobatz'             => '柯巴茲',
        'kuradeel'           => '克拉帝爾',
        // 'kuradeel2'          => '克拉帝爾',       # 桐人（微笑棺木成員）
        'lisbeth'            => '莉茲貝特',
        'liuBei'             => '劉備',
        'machamp'            => '星爆怪力',
        'muguruma'           => '六車拳西',
        'oberon'             => '奧伯龍',
        // 'oberon2'            => '奧伯龍',         # 奧伯龍（亞絲娜的未婚夫）
        'originalKirito'     => '起源桐人',
        'originalKlein'      => '起源克萊因',
        'pina'               => '畢娜',
        'rabbit'             => '雜燴兔',
        'recon'              => '雷根',
        'reiner'             => '萊納·布朗',
        'rosalia'            => '羅莎莉雅',
        'sado'               => '茶渡泰虎',
        'scaredRabbit'       => '驚恐雜燴兔',
        'silica'             => '西莉卡',
        'sinon'              => '詩乃',
        // 'starburst'          => '桐人',           # 桐人（雙刀劍士）
        'stone'              => '石頭',
        'sunsetAndTwoSwords' => '夕陽下的雙刀',
        'theGleameyes'       => '閃耀魔眼',
        'xmasAgil'           => '兩隻麋鹿',
        'xmasGleameyes'      => '聖誕魔眼',
        'xmasKirito'         => '聖誕桐人',
        'xmasKlein'          => '聖誕克萊因',
        'xmasLisbeth'        => '聖誕莉茲貝特',
        'xmasSilica'         => '聖誕西莉卡',
        'xmasSinon'          => '聖誕詩乃',
        'yuuki'              => '有紀',

        # 以下是還沒拿過的角色，待確認
        // 'aizen2'             => '藍染惣右介',     # 藍染惣右介（虛圈統治者）
        // 'aloSilica'          => 'ALO西莉卡',
        // 'aloSinon'           => 'ALO詩乃',
        // 'armour'             => '萊納·布朗',      # 萊納·布朗（鎧之巨人）
        // 'awakenedKobatz'     => '柯巴茲',         # 柯巴茲（覺醒之人）
        // 'blacktea'           => '藍染的紅茶',
        // 'coolEugeo'          => '尤吉歐',         # 尤吉歐（Cool尤吉歐），死亡狀態
        // 'coolKirito'         => '酷桐人',
        // 'dagger'             => '帶毒的匕首',
        // 'dagger2'            => '帶毒的匕首',     # 帶毒的匕首（強大風精靈所持有的短刀）
        // 'deadRecon'          => '亡者雷根',
        // 'eagle'              => '陸行戰鬥鷹',
        // 'eagleHorse'         => '飆速馬',
        // 'fadedRecon'         => '淡忘的回憶',
        // 'ggoKirito'          => 'GGO桐人',
        // 'godRecon'           => '神',
        // 'goldenRecon'        => '黃金雷根',
        // 'gorillaCrow'        => '猩爆鴉',
        // 'gorillaKing'        => '猩爆首領',
        // 'heathcliff'         => '希茲克利夫',     # 希茲克利夫（漢壽亭侯），作者（茅場晶彥）帳號
        // 'hinamori-killed'    => '雛森桃',         # 雛森桃（刀鞘），死亡狀態
        // 'leafa2'             => '莉法',
        // 'reconBack'          => '一個強大的背影',
        // 'silicon'            => '西莉根',
        // 'skyLisbeth'         => '莉茲貝特',       # 莉茲貝特（其實我…喜歡桐人啊！）
        // 'starburstFace'      => '星爆臉',
        // 'tenThousand'        => '一萬',
        // 'theYuuki'           => '有紀',           # 真正的有紀，不是牙紀
        // 'uwKirito2'          => 'Underworld桐人',
        // 'yuuki24'            => '有紀'            # 玩家列表榜首有紀，用來拿真・有紀的 NPC，應該是不可轉生
    ];
}
