<?php

/**
 * 腳本循環執行中遇到網路或其他非本地程式問題，導致重試達最大次數仍然失敗時，是否嚴格地按照邏輯跳出，結束執行
 */
define('STRICT_GO_TO_END_WHEN_FAIL', false);
