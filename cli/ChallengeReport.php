<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use Lib\Helper;
use Lib\CliHelper;
use App\Constant;
use App\MyKirito;

# 由命令行參數指定戰報 ID
$option = getopt('', ['rid:', 'output']);
if (!isset($option['rid']) || $option['rid'] === '')
{
    echo CliHelper::colorText('必須指定戰報 ID（rid）！', '#ff8080', true);
    exit(1);
}

# 戰報 ID
$reportId = $option['rid'];

# 輸出模式（預設為寫入檔案）
$writeToFile = isset($option['output']) ? false : true;

# 查閱戰報
$result = MyKirito::getInstance()->getDetailReport($reportId);
if ($result['httpStatusCode'] !== 200)
{
    echo CliHelper::colorText("MyKirito::getDetailReport HTTP 狀態碼：{$result['httpStatusCode']}", '#ff8080', true);
    exit(1);
}
else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
{
    echo CliHelper::colorText("MyKirito::getDetailReport 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", '#ff8080', true);
    exit(1);
}
$response = $result['response'];

$data = $response;

if ($writeToFile)
{
    # 寫入 JSON 檔案
    $directory = STORAGE_DIR . DIRECTORY_SEPARATOR . 'responses' . DIRECTORY_SEPARATOR . 'ChallengeReport';
    if (!is_dir($directory)) mkdir($directory);
    $file = $directory . DIRECTORY_SEPARATOR . $reportId . '.json';
    file_put_contents($file, json_encode($data, 448));
}
else
{
    # 命令行輸出
    echo CliHelper::colorText(json_encode($data, 448), '#aaffff', true);
}
