<?php

namespace App;

use Lib\MyKiritoAPI;

/**
 * 「我的桐人」動作處理類別
 */
class MyKirito
{
    /**
     * 預設空回應
     *
     * @var array
     */
    const DEFAULT_RESPONSE = [
        'httpStatusCode' => 0,
        'error' => [
            'code' => 0,
            'message' => ''
        ],
        'response' => []
    ];

    /**
     * 行動列表
     *
     * @var string[]
     */
    const ACTION = [
        'Bonus' => 'floorBonus',
        'Hunt'  => 'hunt2',
        'Train' => 'train2',
        'Eat'   => 'eat2',
        'Girl'  => 'girl2',
        'Good'  => 'good2',
        'Sit'   => 'sit2',
        'Fish'  => 'fish2',
        '1h'    => '1h',
        '2h'    => '2h',
        '4h'    => '4h',
        '8h'    => '8h'
    ];

    /**
     * 金手指密鑰
     */
    const SECRET = [
        'Klein'     => 'ebd2aQD/CK8=',    // 克萊因
        'Kibaou'    => 'j2dp1BEaPak=',    // 牙王
        'Hitsugaya' => '96Qge9WpEi0=',    // 日番谷冬獅郎
        'Muguruma'  => 'gc/hgFegpb8='     // 六車拳西
    ];

    /**
     * API 連接物件
     *
     * @var MyKiritoAPI
     */
    protected $_conn;

    /**
     * 物件單一實例
     *
     * @var self|null
     */
    protected static $_uniqueInstance = null;

    /**
     * 取得物件實例
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$_uniqueInstance === null) self::$_uniqueInstance = new self;
        return self::$_uniqueInstance;
    }

    /**
     * 建構子
     *
     * @return void
     */
    protected function __construct()
    {
        $this->_conn = MyKiritoAPI::getInstance();
    }

    /**
     * 取得玩家個人資料
     *
     * @param  string  $player  當前玩家暱稱
     * @return array
     */
    public function getPersonalData(string $player): array
    {
        $token = PLAYER[$player]['Token'];
        return $this->_conn->get('my-kirito', $token);
    }

    /**
     * 更新玩家個人狀態
     *
     * @param  string  $player  當前玩家暱稱
     * @param  string  $status  要更改的玩家狀態
     * @return array
     */
    public function updatePersonalStatus(string $player, string $status): array
    {
        $token = PLAYER[$player]['Token'];
        $payload = [
            'status' => $status
        ];
        return $this->_conn->post('my-kirito/status', $token, $payload);
    }

    /**
     * Undocumented function
     *
     * @param  string  $player  當前玩家暱稱
     * @param  string  $action  行動代碼  
     *                          可用值（參見 `self::ACTION` 行動列表常數）：  
     *                          - `Bonus`: 領取樓層獎勵  
     *                          - `Hunt`:  狩獵兔肉  
     *                          - `Train`: 自主訓練  
     *                          - `Eat`:   外出野餐  
     *                          - `Girl`:  汁妹  
     *                          - `Good`:  做善事  
     *                          - `Sit`:   坐下休息  
     *                          - `Fish`:  釣魚  
     *                          - `1h`:    修行1小時  
     *                          - `2h`:    修行2小時  
     *                          - `4h`:    修行4小時  
     *                          - `8h`:    修行8小時
     * @return array
     */
    public function doAction(string $player, string $action): array
    {
        $uid = PLAYER[$player]['ID'];
        $token = PLAYER[$player]['Token'];
        $payload = [
            'action' => self::ACTION[$action]
        ];
        return $this->_conn->post("my-kirito/doaction?u={$uid}", $token, $payload);
    }

    /**
     * 取得玩家列表（指定等級和頁數）
     *
     * @param  string   $player       當前玩家暱稱
     * @param  integer  $playerLevel  要查詢的玩家等級
     * @param  integer  $listPage     列表頁數
     * @return array
     */
    public function getUserList(string $player, int $playerLevel, int $listPage): array
    {
        $token = PLAYER[$player]['Token'];
        return $this->_conn->get("user-list?lv={$playerLevel}&page={$listPage}", $token);
    }

    /**
     * 搜尋指定暱稱的玩家（完全比對）  
     * 可查到簡略的基本資料，可從其中的 `uid` 進一步呼叫 `self::getPlayerById` 查詢較詳細的資料
     *
     * @param  string   $player    當前玩家暱稱
     * @param  string   $userName  要搜尋的玩家暱稱
     * @return array
     */
    public function getPlayerByName(string $player, string $userName): array
    {
        $token = PLAYER[$player]['Token'];
        $userName = urlencode($userName);
        return $this->_conn->get("search?nickname={$userName}", $token);
    }

    /**
     * 由 ID 查詢較詳細的玩家資料
     *
     * @param  string  $player  當前玩家暱稱
     * @param  string  $userId  要搜尋的玩家 ID
     * @return array
     */
    public function getPlayerById(string $player, string $userId): array
    {
        $token = PLAYER[$player]['Token'];
        return $this->_conn->get("profile/{$userId}", $token);
    }

    /**
     * 指定玩家暱稱（完全比對），查詢較詳細的玩家資料
     *
     * 為 `self::getPlayerByName` 和 `self::getPlayerById` 的綜合
     *
     * @param  string $player    當前玩家暱稱
     * @param  string $userName  要搜尋的玩家暱稱
     * @return array
     */
    public function getDetailByPlayerName(string $player, string $userName): array
    {
        $token = PLAYER[$player]['Token'];
        $userName = urlencode($userName);
        $userData = $this->_conn->get("search?nickname={$userName}", $token);
        if (count($userData['response']['userList']) > 0)
        {
            # 查得玩家 ID
            $userId = $userData['response']['userList'][0]['uid'];
            return $this->_conn->get("profile/{$userId}", $token);
        }

        return self::DEFAULT_RESPONSE;
    }

    /**
     * 指定對手暱稱及挑戰類型進行對戰
     *
     * @param  string   $player         當前玩家暱稱
     * @param  string   $userName       對手玩家暱稱
     * @param  integer  $challengeType  挑戰類型  
     *                                  可用值： 
     *                                  - `0`: 友好切磋 
     *                                  - `1`: 認真對決  
     *                                  - `2`: 決一死戰  
     *                                  - `3`: 我要超渡你  
     * @param  string   $shout          喊話內容，可忽略
     * @return array
     */
    public function chellenge(string $player, string $userName, int $challengeType, string $shout = ''): array
    {
        $token = PLAYER[$player]['Token'];

        $opponent = $this->getDetailByPlayerName($player, $userName);
        if (isset($opponent['response']['profile']))
        {
            $opponentUID = $opponent['response']['profile']['_id'];
            $opponentLevel = $opponent['response']['profile']['lv'];
            $payload = [
                'type' => $challengeType,
                'opponentUID' => $opponentUID,
                'shout' => $shout,
                'lv' => $opponentLevel
            ];

            # 對戰
            $result = $this->_conn->post('challenge', $token, $payload);

            # 從對戰時間取得戰報 ID，並加入回應資料
            $challengeTime = $result['response']['myKirito']['lastChallenge'];
            $report = $this->getThisAttackReport($player, $opponentUID, $challengeTime);
            $result['reportId'] = $report['_id'];

            # 查詢當前玩家是否死亡，並加入回應資料
            $personalData = $this->getPersonalData($player);
            $result['dead']['me'] = $personalData['response']['dead'];

            # 查詢對手玩家是否死亡，並加入回應資料
            $opponentData = $this->getDetailByPlayerName($player, $userName);
            $result['dead']['opponent'] = $opponentData['response']['profile']['dead'];

            return $result;
        }

        # 對手不存在時回應預設的空值陣列
        return self::DEFAULT_RESPONSE;
    }

    /**
     * 取得玩家所在樓層 BOSS 的資料
     *
     * @param  string  $player  當前玩家暱稱
     * @return array
     */
    public function getBoss(string $player): array
    {
        return $this->_conn->get('boss', PLAYER[$player]['Token']);
    }

    /**
     * 挑戰玩家所在樓層 BOSS
     *
     * @param  string  $player  當前玩家暱稱
     * @return array
     */
    public function challengeBoss(string $player): array
    {
        # 對戰
        $result = $this->_conn->post('boss/challenge', PLAYER[$player]['Token']);

        # 從對戰時間取得戰報 ID，並加入回應資料
        $challengeTime = $result['response']['myKirito']['lastBossChallenge'];
        $report = $this->getThisBossReport($player, $challengeTime);
        $result['reportId'] = $report['_id'];

        return $result;
    }

    /**
     * 取得玩家成就資料
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getAchievements(string $player): array
    {
        return $this->_conn->get('achievements', PLAYER[$player]['Token']);
    }

    /**
     * 取得成就榜資料
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getAchievementRank(string $player): array
    {
        return $this->_conn->get('achievement-ranking', PLAYER[$player]['Token']);
    }

    /**
     * 取得名人堂資料
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getHallOfFame(string $player): array
    {
        return $this->_conn->get('hall-of-fame', PLAYER[$player]['Token']);
    }

    /**
     * 取得當前玩家的已解鎖角色
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getUnlockedCharacters(string $player): array
    {
        return $this->_conn->get('my-kirito/unlocked-characters', PLAYER[$player]['Token']);
    }

    /**
     * 令當前玩家轉生
     *
     * @param  string  $player   玩家暱稱
     * @param  array   $payload  轉生設定  
     *                           因為轉生帶的參數比較多，故由使用者在呼叫本方法前先設定好再代入  
     *                           可用的選項有：  
     *                           - `character`: 角色名稱，如 `kirito`、`klein` 等  
     *                                          設為未解鎖角色會回 `400 Bad Request`  
     *                           - `rattrs`: 轉生點分配，其下又分為 `hp`（HP）、`atk`（攻擊）、  
     *                                       `def`（防禦）、`stm`（體力）、`agi`（敏捷）、`spd`（反應速度）、  
     *                                       `tec`（技巧）、`int`（智力）、`lck`（幸運）共 9 項  
     *                                       亂設（超出可設範圍）會回 `400 Bad Request` 及 `error: 點數分配錯誤`  
     *                           - `useReset`: 是否使用洗白點數  
     *                           - `useResetBoss`: 是否重置樓層
     * @return array
     */
    public function reincarnation(string $player, array $payload): array
    {
        $token = PLAYER[$player]['Token'];
        return $this->_conn->post('my-kirito/reincarnation', $token, $payload);
    }

    /**
     * 檢視當前玩家的防守戰報
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getDefenseReports(string $player): array
    {
        return $this->_conn->get('reports?filter=def', PLAYER[$player]['Token']);
    }

    /**
     * 檢視當前玩家的攻擊戰報
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getAttackReports(string $player): array
    {
        return $this->_conn->get('reports?filter=atk', PLAYER[$player]['Token']);
    }

    /**
     * 依當前玩家暱稱、對手玩家 ID 及對戰時間查詢攻擊戰報
     *
     * @param  string   $player      玩家暱稱
     * @param  string   $opponentId  對手玩家 ID
     * @param  integer  $timestamp   對戰時間（毫秒級時間戳）
     * @return array
     */
    public function getThisAttackReport(string $player, string $opponentId, int $timestamp): array
    {
        $result = $this->getAttackReports($player);
        $reports = $result['response']['reports'];
        foreach ($reports as $report)
        {
            if ($report['b']['uid'] === $opponentId && $report['timestamp'] === $timestamp)
            {
                return $report;
            }
        }
        return [];
    }

    /**
     * 檢視當前玩家的攻擊戰報
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getBossReports(string $player): array
    {
        return $this->_conn->get('reports?filter=boss', PLAYER[$player]['Token']);
    }

    /**
     * 依當前玩家暱稱及對戰時間查詢 BOSS 戰報
     *
     * @param  string   $player      玩家暱稱
     * @param  integer  $timestamp   對戰時間（毫秒級時間戳）
     * @return array
     */
    public function getThisBossReport(string $player, int $timestamp): array
    {
        $result = $this->getBossReports($player);
        $reports = $result['response']['reports'];
        foreach ($reports as $report)
        {
            if ($report['timestamp'] === $timestamp)
            {
                return $report;
            }
        }
        return [];
    }

    /**
     * 由戰報 ID 查閱詳細戰報
     *
     * @param  string  $reportId  戰報 ID
     * @return array
     */
    public function getDetailReport(string $reportId): array
    {
        return $this->_conn->get("https://mykirito-storage.b-cdn.net/reports/{$reportId}.json");
    }

    /**
     * 使用在主頁面輸入金手指的方法解鎖角色
     *
     * 背後其實是傳遞一個帶特定密鑰的 POST 請求給伺服器
     *
     * @param  string $player     玩家暱稱
     * @param  string $character  角色名稱  
     *                            截至 2022-02-10 為止，可用 4 個角色，輸入值分別為：  
     *                            - `Klein`：克萊因
     *                            - `Kibaou`：牙王
     *                            - `Hitsugaya`：日番谷冬獅郎
     *                            - `Muguruma`：六車拳西
     * @return array
     */
    public function unlockCharacterBySecret(string $player, string $character): array
    {
        $token = PLAYER[$player]['Token'];
        $payload = [
            'secret' => self::SECRET[$character]
        ];
        return $this->_conn->post('my-kirito/unlock', $token, $payload);
    }
}
