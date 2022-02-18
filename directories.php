<?php

return [

    # 設定（configs）資料夾
    CONFIG_DIR,

    # 日誌（logs）類資料夾
    LOG_DIR,
    LOG_DIR . DIRECTORY_SEPARATOR . 'AutoAction',
    LOG_DIR . DIRECTORY_SEPARATOR . 'AutoChallenge',
    LOG_DIR . DIRECTORY_SEPARATOR . 'TelegramBot',
    LOG_DIR . DIRECTORY_SEPARATOR . 'TelegramBot' . DIRECTORY_SEPARATOR . 'AutoAction',
    LOG_DIR . DIRECTORY_SEPARATOR . 'TelegramBot' . DIRECTORY_SEPARATOR . 'AutoChallenge',

    # 回應及簡要日誌（responses）類資料夾
    STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses',
    STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . 'PersonalOverview',
    STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . 'ChallengeReport',
    STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . 'AutoAction',
    STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . 'AutoChallenge',

    # 檔案鎖資料夾
    STORAGE_DIR . DIRECTORY_SEPARATOR . 'filelocks',
    STORAGE_DIR . DIRECTORY_SEPARATOR . 'filelocks' . DIRECTORY_SEPARATOR . 'AutoAction',
    STORAGE_DIR . DIRECTORY_SEPARATOR . 'filelocks' . DIRECTORY_SEPARATOR . 'AutoChallenge'

];
