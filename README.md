# 我的桐人自動外掛後端腳本<br>MyKirito Trainer Backend Scripts

> 2023 年度預計將全面翻新、改版，暫定名稱為 [MyKirito Proxy Trainer](https://github.com/Wujidadi/MyKirito-Proxy-Trainer)（目前仍在開發初始階段，故設為私有倉庫）  
> Will be updated and revised in 2023 with the new project name [MyKirito Proxy Trainer](https://github.com/Wujidadi/MyKirito-Proxy-Trainer) (now a private repository for the devolopment is still in a very early stage)

## 前置作業 Pre-works

### 專案安裝起手式（初始化） Quickstart

* 執行專案根目錄下的 `install` 腳本  
  Run `install` at project root

### 從網頁取得當前玩家令牌 Get Player Token from the Webpage

* `F12` 或 `Ctrl + Shift + I` 開啟控制台，輸入 `localStorage.getItem('token')`  
  可得到以小數點分隔為 3 部分的字串，即為玩家 token  
  其中第 1 部分為玩家 ID  
  Go to the console by pressing `F12` or `Ctrl + Shift + I` and send `localStorage.getItem('token')`  
  to get a string separated as 3 parts by points  
  Player ID is the 1st part

## 使用方式 Usage

### 參考指令 Commands for References

#### 查閱玩家個人資料 Get Player Personal Data

* `php cli/PersonalOverview.php --player=TSK`
* `php cli/PersonalOverview.php --player=Amon --output`
* `php cli/PersonalOverview.php --player Taras`

#### 查閱詳細戰報 Get the Detail Challenge Report

* `php cli/ChallengeReport.php --rid=620394575d8eb3992a14a536`
* `php cli/ChallengeReport.php --rid=62050c255d8eb3992a15329e --output`

#### 自動行動（指定複數一般行動類型，可領取樓層獎勵）<br>Auto Action (Multiple action types could be set; floor bonus could be taken)

* `php cli/AutoAction.php --player=TSK --output`
* `php cli/AutoAction.php --player=Amon --action=4 --output`

#### 自動挑戰（可指定複數對手，死了可帶參數自動復活，若有對手 TOKEN 還可復活死掉的對手）<br>Auto Challenge (Multiple opponents could be set; Enable auto reincaration with corresponding argument assigned; Opponents could also be reincarated if we have their tokens)

* `php cli/AutoChallenge.php --player=TSK --opp=Taras --type=0 --rez --output`
* `php cli/AutoChallenge.php --player=Amon --opp=TSK,Taras --type=3 --shout=我要超渡你 --rez --output`

## PHP 批次檔參考 PHP Batch Commands for References

```php
exec("/usr/bin/php cli/AutoAction.php --player=TSK > /dev/null &");
exec("/usr/bin/php cli/AutoChallenge.php --player=TSK --opp=Taras --type=0 --rez > /dev/null &");
exec("/usr/bin/php cli/AutoAction.php --player=Amon --action=4 > /dev/null &");
exec("/usr/bin/php cli/AutoChallenge.php --player=Amon --opp=Taras --type=0 --rez > /dev/null &");
```
