<?php

ini_set('precision', 16);

chdir(__DIR__);

define('BASE_DIR', __DIR__);

define('CONFIG_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'configs');
define('VENDOR_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'vendor');
define('LIB_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'lib');
define('APP_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'app');
define('STORAGE_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'storage');

define('LOG_DIR', STORAGE_DIR . DIRECTORY_SEPARATOR . 'logs');

require_once VENDOR_DIR . DIRECTORY_SEPARATOR . 'autoload.php';

require_once CONFIG_DIR . DIRECTORY_SEPARATOR . 'IdTokens.php';
