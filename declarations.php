<?php

ini_set('precision', 16);

chdir(__DIR__);

# 專案根目錄
define('BASE_DIR', __DIR__);

# 頂層子目錄
define('CONFIG_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'configs');
define('VENDOR_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'vendor');
define('LIB_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'lib');
define('APP_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'app');
define('STORAGE_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'storage');

# 第二層子目錄
define('LOG_DIR', STORAGE_DIR . DIRECTORY_SEPARATOR . 'logs');

# 命令行退出狀態碼
define('CLI_OK', 0);
define('CLI_ERROR', 1);
define('CLI_ABNORMAL', 2);
define('CLI_EXECUTING', 3);

# 命令行輸出字體顏色
define('CLI_TEXT_INFO', '#AAFFFF');
define('CLI_TEXT_CAUTION', '#FFC080');
define('CLI_TEXT_WARNING', '#EDDD83');
define('CLI_TEXT_ERROR', '#FF8080');

# Autoload
require_once VENDOR_DIR . DIRECTORY_SEPARATOR . 'autoload.php';
