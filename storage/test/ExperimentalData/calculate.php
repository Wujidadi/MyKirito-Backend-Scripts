<?php

chdir(__DIR__);

$precision = 16;

$jetLag = 28800;

$actions = json_decode(file_get_contents('AutoAction.json'), true);
$challenges = json_decode(file_get_contents('AutoChallenge.json'), true);

$totalActionTime = '0';
$totalChallengeTime = '0';

if (count($actions) > 0)
{
    $count = (string) count($actions);

    foreach ($actions as $time => $times)
    {
        $secInDay = (string) (strtotime($time) + $jetLag) % 86400;
        $avgTime = bcdiv($secInDay, $times, $precision);
        $totalActionTime = bcadd($totalActionTime, $avgTime, $precision);

        echo "[{$time}] {$avgTime}\n";
    }

    echo PHP_EOL;

    $avgActionTime = bcdiv($totalActionTime, $count, $precision);
    $timesPerDay = bcdiv('86400', $avgActionTime, $precision);

    echo "Average: {$avgActionTime} seconds\n";
    echo "Per Day: {$timesPerDay} times\n";

    echo PHP_EOL;    // 分隔行動與挑戰數據
}

if (count($challenges) > 0)
{
    $count = (string) count($challenges);

    foreach ($challenges as $time => $times)
    {
        $secInDay = (string) (strtotime($time) + $jetLag) % 86400;
        $avgTime = bcdiv($secInDay, $times, $precision);
        $totalChallengeTime = bcadd($totalChallengeTime, $avgTime, $precision);

        echo "[{$time}] {$avgTime}\n";
    }

    echo PHP_EOL;

    $avgChallengeTime = bcdiv($totalChallengeTime, $count, $precision);
    $timesPerDay = bcdiv('86400', $avgChallengeTime, $precision);

    echo "Average: {$avgChallengeTime} seconds\n";
    echo "Per Day: {$timesPerDay} times\n";
}
