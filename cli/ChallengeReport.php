<?php

chdir(__DIR__);

require_once '../entrypoint.php';

use Lib\Helpers\CliHelper;
use App\MyKirito;

# 由命令行參數指定戰報 ID 及輸出模式
$option = getopt('', ['rid:', 'output']);

# 戰報 ID
if (!isset($option['rid']) || $option['rid'] === '')
{
    echo CliHelper::colorText('必須指定戰報 ID（rid）！', CLI_TEXT_ERROR, true);
    exit(CLI_ERROR);
}
$reportId = $option['rid'];

# 輸出模式（預設為僅寫入檔案，不顯示於終端機）
$writeToFile = !isset($option['output']);

# 查閱戰報
$result = MyKirito::getDetailReport($reportId);
if ($result['httpStatusCode'] !== 200)
{
    echo CliHelper::colorText("MyKirito::getDetailReport HTTP 狀態碼：{$result['httpStatusCode']}", CLI_TEXT_ERROR, true);
    exit(CLI_ERROR);
}
else if ($result['error']['code'] !== 0 || $result['error']['message'] !== '')
{
    echo CliHelper::colorText("MyKirito::getDetailReport 錯誤代碼：{$result['error']['code']}，錯誤訊息：{$result['error']['message']}", CLI_TEXT_ERROR, true);
    exit(CLI_ERROR);
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
    echo CliHelper::colorText(json_encode($data, 448), CLI_TEXT_INFO, true);
}
