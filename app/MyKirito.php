<?php

namespace App;

use Lib\Helpers\Helper;
use Lib\Helpers\CliHelper;
use Lib\Log\Logger;
use Lib\MyKiritoAPI;
use Lib\FakeAPI;

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
    public const DEFAULT_RESPONSE = [
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
    public const ACTION = [
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
     *
     * 每隔一段時間會變，所以沒什麼實用價值
     *
     * @var string[]
     */
    public const SECRET = [
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
     * 當前玩家暱稱
     *
     * 因為幾乎所有 API 都需要帶玩家 Token，所以把玩家暱稱置於單例模式建構項
     *
     * @var string
     */
    protected $_player;

    /**
     * 物件單一實例
     *
     * @var self|null
     */
    protected static $_uniqueInstance = null;

    /**
     * 取得物件實例
     *
     * @param  string  $player  當前玩家暱稱
     * @return self
     */
    public static function getInstance(string $player)
    {
        if (self::$_uniqueInstance === null) {
            self::$_uniqueInstance = new self($player);
        }
        return self::$_uniqueInstance;
    }

    /**
     * 建構子
     *
     * @param  string  $player  當前玩家暱稱
     * @return void
     */
    protected function __construct(string $player)
    {
        $this->_conn = MyKiritoAPI::getInstance();
        $this->_player = $player;
    }

    /**
     * 取得玩家個人資料
     *
     * @return array
     */
    public function getPersonalData(): array
    {
        $token = PLAYER[$this->_player]['Token'];
        return $this->_conn->get('my-kirito', $token);
    }

    /**
     * 更新玩家個人狀態
     *
     * 附註：官方更新個人狀態的冷卻時間其實沒有作用，冷卻時間未過照樣能發 API 更新
     *
     * @param  string  $status  要更改的玩家狀態
     * @return array
     */
    public function updatePersonalStatus(string $status): array
    {
        $token = PLAYER[$this->_player]['Token'];
        $payload = [
            'status' => $status
        ];
        return $this->_conn->post('my-kirito/status', $token, $payload);
    }

    /**
     * 行動
     *
     * @param  string  $action  行動代碼，參見 `self::ACTION` 行動列表常數）：
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
    public function doAction(string $action): array
    {
        # 偽請求
        // return FakeAPI::getInstance($this->_player)->request();

        $uid = PLAYER[$this->_player]['ID'];
        $token = PLAYER[$this->_player]['Token'];
        $payload = [
            'action' => self::ACTION[$action]
        ];
        return $this->_conn->post("my-kirito/doaction?u={$uid}", $token, $payload);
    }

    /**
     * 取得玩家列表（指定等級和頁數）
     *
     * @param  integer  $playerLevel  要查詢的玩家等級
     * @param  integer  $listPage     列表頁數
     * @return array
     */
    public function getUserList(int $playerLevel, int $listPage): array
    {
        $token = PLAYER[$this->_player]['Token'];
        return $this->_conn->get("user-list?lv={$playerLevel}&page={$listPage}", $token);
    }

    /**
     * 搜尋指定暱稱的玩家（完全比對）
     * 可查到簡略的基本資料，可從其中的 `uid` 進一步呼叫 `self::getPlayerById` 查詢較詳細的資料
     *
     * @param  string   $userName  要搜尋的玩家暱稱
     * @return array
     */
    public function getPlayerByName(string $userName): array
    {
        $token = PLAYER[$this->_player]['Token'];
        $userName = urlencode($userName);
        return $this->_conn->get("search?nickname={$userName}", $token);
    }

    /**
     * 由 ID 查詢較詳細的玩家資料
     *
     * @param  string  $userId  要搜尋的玩家 ID
     * @return array
     */
    public function getPlayerById(string $userId): array
    {
        $token = PLAYER[$this->_player]['Token'];
        return $this->_conn->get("profile/{$userId}", $token);
    }

    /**
     * 指定玩家暱稱（完全比對），查詢較詳細的玩家資料
     *
     * 為 `self::getPlayerByName` 和 `self::getPlayerById` 的綜合
     *
     * @param  string $userName  要搜尋的玩家暱稱
     * @return array
     */
    public function getDetailByPlayerName(string $userName): array
    {
        $token = PLAYER[$this->_player]['Token'];
        $userName = urlencode($userName);
        $userData = $this->_conn->get("search?nickname={$userName}", $token);
        if (is_array($userData) &&
            is_array($userData['response']) &&
            is_array($userData['response']['userList']) &&
            count($userData['response']['userList']) > 0) {
            # 查得玩家 ID
            $userId = $userData['response']['userList'][0]['uid'];
            return $this->_conn->get("profile/{$userId}", $token);
        }

        return self::DEFAULT_RESPONSE;
    }

    /**
     * 指定對手暱稱及挑戰類型進行對戰
     *
     * @param  string   $userName       對手玩家暱稱
     * @param  integer  $challengeType  挑戰類型
     *                                  - `0`: 友好切磋
     *                                  - `1`: 認真對決
     *                                  - `2`: 決一死戰
     *                                  - `3`: 我要超渡你
     * @param  string   $shout          喊話內容，可忽略
     * @return array
     */
    public function challenge(string $userName, int $challengeType, string $shout = ''): array
    {
        $token = PLAYER[$this->_player]['Token'];

        $opponent = $this->getDetailByPlayerName($userName);
        if (isset($opponent['response']['profile'])) {
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

            if (isset($result['response']['myKirito'])) {
                # 從對戰時間取得戰報 ID
                $challengeTime = $result['response']['myKirito']['lastChallenge'];
                $report = $this->getThisAttackReport($opponentUID, $challengeTime);

                # 查詢當前玩家是否死亡
                $personalData = $this->getPersonalData();

                # 查詢對手玩家是否死亡
                $opponentData = $this->getDetailByPlayerName($userName);

                # 建構回應資料
                if (is_array($report) && isset($report['_id']) &&
                    $this->isPersonalDataLegal($personalData) &&
                    $this->isOpponentDataLegal($opponentData)) {
                    $result['reportId'] = $report['_id'];
                    $result['dead']['me'] = $personalData['response']['dead'];
                    $result['dead']['opponent'] = $opponentData['response']['profile']['dead'];
                }
            }

            return $result;
        }

        # 對手不存在時或回應錯誤時，返回預設的空值陣列
        return self::DEFAULT_RESPONSE;
    }

    private function isPersonalDataLegal(array $personalData): bool
    {
        return is_array($personalData) &&
               isset($personalData['response']) &&
               is_array($personalData['response']);
    }

    private function isOpponentDataLegal(array $opponentData): bool
    {
        return is_array($opponentData) &&
               isset($opponentData['response']) &&
               is_array($opponentData['response']) &&
               isset($opponentData['response']['profile']);
    }

    /**
     * 取得玩家所在樓層 BOSS 的資料
     *
     * @return array
     */
    public function getBoss(): array
    {
        return $this->_conn->get('boss', PLAYER[$this->_player]['Token']);
    }

    /**
     * 挑戰玩家所在樓層 BOSS
     *
     * @return array
     */
    public function challengeBoss(): array
    {
        # 對戰
        $result = $this->_conn->post('boss/challenge', PLAYER[$this->_player]['Token']);

        if (isset($result['response']['myKirito']) && isset($result['response']['myKirito']['lastBossChallenge'])) {
            # 從對戰時間取得戰報 ID，並加入回應資料
            $challengeTime = $result['response']['myKirito']['lastBossChallenge'];
            $report = $this->getThisBossReport($challengeTime);
            $result['reportId'] = $report['_id'];

            return $result;
        } else {
            return $result;
        }
    }

    /**
     * 取得玩家成就資料
     *
     * @return array
     */
    public function getAchievements(): array
    {
        return $this->_conn->get('achievements', PLAYER[$this->_player]['Token']);
    }

    /**
     * 取得成就榜資料
     *
     * @return array
     */
    public function getAchievementRank(): array
    {
        return $this->_conn->get('achievement-ranking', PLAYER[$this->_player]['Token']);
    }

    /**
     * 取得名人堂資料
     *
     * @return array
     */
    public function getHallOfFame(): array
    {
        return $this->_conn->get('hall-of-fame', PLAYER[$this->_player]['Token']);
    }

    /**
     * 取得當前玩家的已解鎖角色
     *
     * @param  string  $player  玩家暱稱
     * @return array
     */
    public function getUnlockedCharacters(): array
    {
        return $this->_conn->get('my-kirito/unlocked-characters', PLAYER[$this->_player]['Token']);
    }

    /**
     * 令當前玩家轉生
     *
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
     * @param  string  $player   玩家暱稱；忽略時自動代入當前玩家暱稱
     * @return array
     */
    public function reincarnation(array $payload, string $player = null): array
    {
        $player = $player ?? $this->_player;
        $token = PLAYER[$player]['Token'];
        return $this->_conn->post('my-kirito/reincarnation', $token, $payload);
    }

    /**
     * 檢視當前玩家的防守戰報
     *
     * @return array
     */
    public function getDefenseReports(): array
    {
        return $this->_conn->get('reports?filter=def', PLAYER[$this->_player]['Token']);
    }

    /**
     * 檢視當前玩家的攻擊戰報
     *
     * @return array
     */
    public function getAttackReports(): array
    {
        return $this->_conn->get('reports?filter=atk', PLAYER[$this->_player]['Token']);
    }

    /**
     * 依當前玩家暱稱、對手玩家 ID 及對戰時間查詢攻擊戰報
     *
     * @param  string   $opponentId  對手玩家 ID
     * @param  integer  $timestamp   對戰時間（毫秒級時間戳）
     * @return array
     */
    public function getThisAttackReport(string $opponentId, int $timestamp): array
    {
        $result = $this->getAttackReports();
        if (isset($result['response']) && is_array($result['response']) && isset($result['response']['reports'])) {
            $reports = $result['response']['reports'];
            foreach ($reports as $report) {
                if ($report['b']['uid'] === $opponentId && $report['timestamp'] === $timestamp) {
                    return $report;
                }
            }
        }
        return [];
    }

    /**
     * 檢視當前玩家的 BOSS 戰報
     *
     * @return array
     */
    public function getBossReports(): array
    {
        return $this->_conn->get('reports?filter=boss', PLAYER[$this->_player]['Token']);
    }

    /**
     * 依當前玩家暱稱及對戰時間查詢 BOSS 戰報
     *
     * @param  integer  $timestamp   對戰時間（毫秒級時間戳）
     * @return array
     */
    public function getThisBossReport(int $timestamp): array
    {
        $result = $this->getBossReports();
        $reports = $result['response']['reports'];
        foreach ($reports as $report) {
            if ($report['timestamp'] === $timestamp) {
                return $report;
            }
        }
        return [];
    }

    /**
     * 由戰報 ID 查閱詳細戰報
     *
     * 靜態方法，故直接呼叫 `MyKiritoAPI` 實例
     *
     * @param  string  $reportId  戰報 ID
     * @return array
     */
    public static function getDetailReport(string $reportId): array
    {
        return MyKiritoAPI::getInstance()->get("https://mykirito-storage.b-cdn.net/reports/{$reportId}.json");
    }

    /**
     * 使用在主頁面輸入金手指的方法解鎖角色
     *
     * 背後其實是傳遞一個帶特定密鑰的 POST 請求給伺服器
     *
     * @param  string $character  角色名稱
     *                            截至 2022-02-10 為止，可用 4 個角色，輸入值分別為：
     *                            - `Klein`：克萊因
     *                            - `Kibaou`：牙王
     *                            - `Hitsugaya`：日番谷冬獅郎
     *                            - `Muguruma`：六車拳西
     * @return array
     */
    public function unlockCharacterBySecret(string $character): array
    {
        $token = PLAYER[$this->_player]['Token'];
        $payload = [
            'secret' => self::SECRET[$character]
        ];
        return $this->_conn->post('my-kirito/unlock', $token, $payload);
    }

    /**
     * 從請求回應結果中取出最新解鎖的角色
     *
     * @param  array  $result   請求回應結果
     * @return string[]|null[]  最新解鎖的角色資訊，包括 `character`、`name` 及 `avatar` 3 個部分
     */
    public static function getNewestUnlockedCharacter(array $result): array
    {
        if (isset($result['response']) && is_array($result['response'])) {
            $response = $result['response'];
            if (isset($response['myKirito']) && is_array($response['myKirito'])) {
                $myKirito = $response['myKirito'];
                if (isset($myKirito['unlockedCharacters']) && is_array($myKirito['unlockedCharacters'])) {
                    $unlockedCharacters = $myKirito['unlockedCharacters'];
                    $last = count($unlockedCharacters) - 1;
                    return $unlockedCharacters[$last];
                }
            }
        }
        return [
            'character' => null,
            'name'      => null,
            'avatar'    => null
        ];
    }

    /**
     * 自動復活
     *
     * @param  string   $player         玩家暱稱
     * @param  string   $character      玩家的角色名稱
     * @param  array[]  $logFiles       日誌檔案路徑
     * @param  boolean  $syncOutput     是否同步輸出於終端機
     * @param  boolean  $isOpp          是否對手玩家，預設為 `false`
     * @return boolean
     */
    public function autoRez(string $player, string $character, array $logFiles, bool $syncOutput, bool $isOpp = false): bool
    {
        $pRez = null;
        $oppNote = '';

        if ($isOpp) {
            $pRez = $player;
            $oppNote = '對手';
        }

        $logTime = Helper::Time();
        $logMessage = "自動復活{$oppNote}玩家 {$player}……";
        Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

        if ($syncOutput) {
            echo CliHelper::colorText($logMessage, CLI_TEXT_WARNING, true);
        }

        # 復活：原角色原地轉生，不動用任何轉生點、洗白點數或重置樓層
        $payload = [
            'character' => $character,
            'rattrs' => [
                'hp' => 0,
                'atk' => 0,
                'def' => 0,
                'stm' => 0,
                'agi' => 0,
                'spd' => 0,
                'tec' => 0,
                'int' => 0,
                'lck' => 0
            ],
            'useReset' => false,
            'useResetBoss' => false
        ];

        # 取得目前轉生點配點
        $userId = $this->getPlayerByName($player)['response']['userList'][0]['uid'];
        $rattrs = array_sum($this->getPlayerById($userId)['response']['profile']['rattrs']);
        $newRattrs = $rattrs;

        # 重試次數
        $retry = 0;

        # 在最大重試次數內，發送轉生請求
        do {
            $result = $this->reincarnation($payload, $pRez);

            # 注意：為了簡化並節省 log 空間，此處所列 reincarnation 方法的參數並非實際應代入的 payload，而是角色名稱
            $context = "MyKirito::reincarnation('{$character}'" . ($isOpp ? ", '{$pRez}'" : '') . ')';

            if ($result['httpStatusCode'] !== 200 || ($result['error']['code'] !== 0 || $result['error']['message'] !== '')) {
                CliHelper::logError($result, $logFiles, $context, $syncOutput);

                # 轉生點有變化時，總是加在智力
                if ($result['httpStatusCode'] === 400 && $result['response']['error'] === '點數分配錯誤') {
                    if (!$payload['useReset']) {
                        $payload['useReset'] = true;
                    }
                    $payload['rattrs']['int'] = ++$newRattrs;

                    $logMessage = "嘗試以 {$newRattrs} 點轉生點全加智力復活……";
                    $logTime = Helper::Time();
                    Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);
                } else {
                    $retry++;
                }

                # 每次重試間隔
                sleep(Constant::RetryInterval);
            } else {
                $logTime = Helper::Time();

                if (($addedRattrs = $newRattrs - $rattrs) > 0) {
                    $briefLog = "{$oppNote}玩家 {$player} 已復活，新增轉生點：{$addedRattrs}";
                    $detailLog = json_encode([
                        'result' => $result,
                        'resetPoint' => $newRattrs,
                        'addedPoint' => $addedRattrs
                    ], 320);
                } else {
                    $briefLog = "{$oppNote}玩家 {$player} 已復活";
                    $detailLog = json_encode($result, 320);
                }

                $logMessage = [
                    'brief' => $briefLog,
                    'detail' => $detailLog
                ];

                Logger::getInstance()->log($logMessage, $logFiles, true, $logTime);

                if ($syncOutput) {
                    # 命令行輸出
                    echo CliHelper::colorText($logMessage['brief'], CLI_TEXT_WARNING, true);
                }

                break;
            }
        } while ($retry < Constant::MaxRetry);

        # 達到重試次數上限仍然失敗
        if ($retry >= Constant::MaxRetry) {
            $logTime = Helper::Time();
            $logMessage = "{$context} 重試 {$retry} 次失敗";
            Logger::getInstance()->log($logMessage, $logFiles, false, $logTime);

            if ($syncOutput) {
                echo CliHelper::colorText($logMessage, CLI_TEXT_ERROR, true);
            }

            return false;
        }

        # 復活成功
        return true;
    }
}
