<?php

/**
 * 玩家角色 ID 與名稱對照表
 *
 * 從 profile API 取得的 `avatar`，當稱號有變化時，有些會變得與初始稱號時的 `avatar` 不同  
 * 可見 `avatar` 與角色 ID 不能直接對應，應該就只是用來存取角色 webp 頭像而已
 *
 * @var string[]
 */
define('PLAYER_CHARACTER', [
    'agil'               => '艾基爾',
    'aizen'              => '藍染惣右介',
    'alice'              => '愛麗絲',
    'aloAsuna'           => 'ALO亞絲娜',
    'aloKirito'          => 'ALO桐人',
    'aloSilica'          => 'ALO西莉卡',
    'aloSinon'           => 'ALO詩乃',
    'asuna'              => '亞絲娜',
    'awakenedKobatz'     => '柯巴茲',        # 柯巴茲（覺醒之人）
    'blackRabbit'        => '黑色雜燴兔',
    'blacktea'           => '藍染的紅茶',
    'blueRecon'          => '色違雷根',
    'bradley'            => '金·布拉德雷',
    'busuna'             => '聖誕巴士',
    'charizardX'         => '超級噴火龍X',
    'connie'             => '鎖鏈的康妮',
    'coolKirito'         => '酷桐人',
    'dagger'             => '帶毒的匕首',
    'deadRecon'          => '亡者雷根',
    'diavel'             => '提亞貝魯',
    'eagle'              => '陸行戰鬥鷹',
    'eagleHorse'         => '飆速馬',
    'eugene'             => '尤金',
    'eugeo'              => '尤吉歐',
    'fadedRecon'         => '淡忘的回憶',
    'fish'               => '努西',
    'geodude'            => '星爆小拳石',
    'ggoKirito'          => 'GGO桐人',
    'godfree'            => '哥德夫利',
    'godRecon'           => '神',
    'goldenRecon'        => '黃金雷根',
    'gorilla'            => '猩爆戰象',
    'gorillaCrow'        => '猩爆鴉',
    'gorillaKing'        => '猩爆首領',
    'graveler'           => '星爆隆隆石',
    'guanYu'             => '關羽',
    'hatsumi'            => '初見泉',
    'heathcliff'         => '希茲克利夫',    # 希茲克利夫（漢壽亭侯），作者（茅場晶彥）帳號，應該是不可用角色
    'hina'               => '天野陽菜',
    'hinamori'           => '雛森桃',
    'hitsugaya'          => '日番谷冬獅郎',
    'honda'              => '本多忠勝',
    'kibaou'             => '牙王',
    'kirito'             => '桐人',
    'kirito74'           => '難道只能使出那一招了嗎',
    'klein'              => '克萊因',
    'kobatz'             => '柯巴茲',
    'kuradeel'           => '克拉帝爾',
    'leafa'              => '莉法',
    'lisbeth'            => '莉茲貝特',
    'liuBei'             => '劉備',
    'machamp'            => '星爆怪力',
    'muguruma'           => '六車拳西',
    'oberon'             => '奧伯龍',
    'originalKirito'     => '起源桐人',
    'originalKlein'      => '起源克萊因',
    'pina'               => '畢娜',
    'rabbit'             => '雜燴兔',
    'recon'              => '雷根',
    'reconBack'          => '一個強大的背影',
    'reiner'             => '萊納·布朗',
    'rosalia'            => '羅莎莉雅',
    'sado'               => '茶渡泰虎',
    'scaredRabbit'       => '驚恐雜燴兔',
    'silica'             => '西莉卡',
    'silicon'            => '西莉根',
    'sinon'              => '詩乃',
    'skyLisbeth'         => '莉茲貝特',      # 莉茲貝特（其實我…喜歡桐人啊！）
    'starburstFace'      => '星爆臉',
    'stone'              => '石頭',
    'sunsetAndTwoSwords' => '夕陽下的雙刀',
    'tenThousand'        => '一萬',
    'theGleameyes'       => '閃耀魔眼',
    'theYuuki'           => '有紀',          # 真正的有紀，不是牙紀
    'uwKirito'           => 'Underworld桐人',
    'xmasAgil'           => '兩隻麋鹿',
    'xmasGleameyes'      => '聖誕魔眼',
    'xmasKirito'         => '聖誕桐人',
    'xmasKlein'          => '聖誕克萊因',
    'xmasLisbeth'        => '聖誕莉茲貝特',
    'xmasSilica'         => '聖誕西莉卡',
    'xmasSinon'          => '聖誕詩乃',
    'yuuki'              => '有紀',
    'yuuki24'            => '有紀'           # 玩家列表榜首有紀，用來拿真・有紀的 NPC，應該是不可用角色
]);

/**
 * 與頭像 ID 對應的實際角色 ID
 *
 * - **key：** 頭像 ID，係透過 profile API 取得的 `avatar` 欄位以小數點分隔的第一段
 * - **value：** 角色的實際 ID，即 `PLAYER_CHARACTER` 常數陣列的 key
 *
 * @var string[]
 */
define('AVATAR_CHARACTER', [
    'agil'               => 'agil',
    'agil2'              => 'agil',              # 艾基爾（頂裝道具店老闆）
    'aizen'              => 'aizen',
    'aizen2'             => 'aizen',
    'alice'              => 'alice',
    'aloAsuna'           => 'aloAsuna',
    'aloKirito'          => 'aloKirito',
    'aloSilica'          => 'aloSilica',
    'aloSinon'           => 'aloSinon',
    'armour'             => 'reiner',            # 萊納·布朗（鎧之巨人）
    'asuna'              => 'asuna',
    'awakenedKobatz'     => 'awakenedKobatz',    # 柯巴茲（覺醒之人）
    'blackRabbit'        => 'blackRabbit',
    'blacktea'           => 'blacktea',
    'blueRecon'          => 'blueRecon',
    'bradley'            => 'bradley',
    'bradley2'           => 'bradley',           # 金·布拉德雷（人造人「憤怒」）
    'busuna'             => 'busuna',
    'charizardX'         => 'charizardX',
    'connie'             => 'connie',
    'coolEugeo'          => 'eugeo',             # 尤吉歐（Cool尤吉歐），死亡狀態
    'coolKirito'         => 'coolKirito',
    'dagger'             => 'dagger',
    'dagger2'            => 'dagger',           # 帶毒的匕首（強大風精靈所持有的短刀）
    'deadRecon'          => 'deadRecon',
    'diavel'             => 'diavel',
    'eagle'              => 'eagle',
    'eagleHorse'         => 'eagleHorse',
    'eugene'             => 'eugene',
    'eugeo'              => 'eugeo',
    'fadedRecon'         => 'fadedRecon',
    'fish'               => 'fish',
    'geodude'            => 'geodude',
    'ggoKirito'          => 'ggoKirito',
    'godfree'            => 'godfree',
    'godRecon'           => 'godRecon',
    'goldenRecon'        => 'goldenRecon',
    'gorilla'            => 'gorilla',
    'gorillaCrow'        => 'gorillaCrow',
    'gorillaKing'        => 'gorillaKing',
    'graveler'           => 'graveler',
    'guanYu'             => 'guanYu',
    'guanYuDevil'        => 'guanYu',            # 關羽（魔）
    'hatsumi'            => 'hatsumi',
    'heathcliff'         => 'heathcliff',        # 希茲克利夫（漢壽亭侯），作者（茅場晶彥）帳號
    'hina'               => 'hina',
    'hinamori'           => 'hinamori',
    'hinamori-killed'    => 'hinamori',          # 雛森桃（刀鞘），死亡狀態
    'hitsugaya'          => 'hitsugaya',
    'honda'              => 'honda',
    'kibaou'             => 'kibaou',
    'kirito'             => 'kirito',
    'kirito2'            => 'kirito',            # 桐人（血盟騎士團成員）
    'kirito3'            => 'kirito',            # 桐人（就憑你這菜B8 笑死）
    'kirito74'           => 'kirito74',
    'klein'              => 'klein',
    'kobatz'             => 'kobatz',
    'kuradeel'           => 'kuradeel',
    'kuradeel2'          => 'kuradeel',          # 克拉帝爾（微笑棺木成員）
    'leafa2'             => 'leafa',
    'lisbeth'            => 'lisbeth',
    'liuBei'             => 'liuBei',
    'machamp'            => 'machamp',
    'muguruma'           => 'muguruma',
    'oberon'             => 'oberon',
    'oberon2'            => 'oberon',            # 奧伯龍（亞絲娜的未婚夫）
    'originalKirito'     => 'originalKirito',
    'originalKlein'      => 'originalKlein',
    'pina'               => 'pina',
    'rabbit'             => 'rabbit',
    'recon'              => 'recon',
    'reconBack'          => 'reconBack',
    'reiner'             => 'reiner',
    'rosalia'            => 'rosalia',
    'sado'               => 'sado',
    'scaredRabbit'       => 'scaredRabbit',
    'silica'             => 'silica',
    'silicon'            => 'silicon',
    'sinon'              => 'sinon',
    'skyLisbeth'         => 'skyLisbeth',        # 莉茲貝特（其實我…喜歡桐人啊！）
    'starburst'          => 'kirito',            # 桐人（雙刀劍士）
    'starburstFace'      => 'starburstFace',
    'stone'              => 'stone',
    'sunsetAndTwoSwords' => 'sunsetAndTwoSwords',
    'tenThousand'        => 'tenThousand',
    'theGleameyes'       => 'theGleameyes',
    'theYuuki'           => 'theYuuki',          # 真正的有紀，不是牙紀
    'uwKirito2'          => 'uwKirito',
    'xmasAgil'           => 'xmasAgil',
    'xmasGleameyes'      => 'xmasGleameyes',
    'xmasKirito'         => 'xmasKirito',
    'xmasKlein'          => 'xmasKlein',
    'xmasLisbeth'        => 'xmasLisbeth',
    'xmasSilica'         => 'xmasSilica',
    'xmasSinon'          => 'xmasSinon',
    'yuuki'              => 'yuuki',             # 牙紀
    'yuuki24'            => 'yuuki24'            # 玩家列表榜首有紀，用來拿真・有紀的 NPC，應該是不可轉生
]);
