# 我的桐人（MyKirito）後端腳本

## 前置作業

### 專案安裝起手式（初始化）

* 執行專案根目錄下的 `install` 腳本

### 從網頁取得當前玩家 Token

* `F12` 或 `Ctrl + Shift + I` 開啟控制台，輸入 `localStorage.getItem('token')`  
  可得到以小數點分隔為 3 部分的字串，即為玩家 token  
  其中第 1 部分為玩家 ID

## 使用方式

### 參考指令

#### 查閱玩家個人資料

* `php cli/PersonalOverview.php --player=TSK`
* `php cli/PersonalOverview.php --player=Amon --output`
* `php cli/PersonalOverview.php --player Taras`

#### 查閱詳細戰報

* `php cli/ChallengeReport.php --rid=620394575d8eb3992a14a536`
* `php cli/ChallengeReport.php --rid=62050c255d8eb3992a15329e --output`

#### 自動練功（指定複數一般行動類型，可領取樓層獎勵）

* `php cli/AutoAction.php --player=TSK --output`
* `php cli/AutoAction.php --player=Amon --action=4 --output`

#### 自動打架（指定複數對手隨機挑戰，死了可帶參數自動復活，若有對手 TOKEN 還可復活死掉的對手）

* `php cli/AutoChallenge.php --player=TSK --opp=Taras --type=0 --rez --output`
* `php cli/AutoChallenge.php --player=Amon --opp=TSK,Taras --type=3 --shout=我要超渡你 --rez --output`

## PHP 批次檔參考

```php
exec("/usr/bin/php cli/AutoAction.php --player=TSK > /dev/null &");
exec("/usr/bin/php cli/AutoChallenge.php --player=TSK --opp=Taras --type=0 --rez > /dev/null &");
exec("/usr/bin/php cli/AutoAction.php --player=Amon --action=4 > /dev/null &");
exec("/usr/bin/php cli/AutoChallenge.php --player=Amon --opp=Taras --type=0 --rez > /dev/null &");
```
