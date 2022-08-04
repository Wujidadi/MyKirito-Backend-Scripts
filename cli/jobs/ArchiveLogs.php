<?php

chdir(__DIR__);

require_once '../../entrypoint.php';

$logDir = [
    '/home/wujidadi/workspaces/MyKirito/storage/logs/AutoAction',
    '/home/wujidadi/workspaces/MyKirito/storage/logs/AutoChallenge',
    '/home/wujidadi/workspaces/MyKirito/storage/logs/Reincarnate',
    '/home/wujidadi/workspaces/MyKirito/storage/logs/TelegramBot/AutoAction',
    '/home/wujidadi/workspaces/MyKirito/storage/logs/TelegramBot/AutoChallenge',
    '/home/wujidadi/workspaces/MyKirito/storage/responses/AutoAction',
    '/home/wujidadi/workspaces/MyKirito/storage/responses/AutoChallenge',
    '/home/wujidadi/workspaces/MyKirito/storage/responses/Reincarnate'
];

$month = date('Ym', strtotime('-1 month'));

foreach ($logDir as $dir)
{
    if (!is_dir("{$dir}/archived"))
    {
        mkdir("{$dir}/archived");
    }

    $files = glob("{$dir}/*_{$month}*.log");

    $players = [];

    foreach ($files as $file)
    {
        $basename = basename($file);
        $player = explode('_', $basename)[0];
        if (!in_array($player, $players))
        {
            $players[] = $player;
        }
    }

    foreach ($players as $player)
    {
        shell_exec("7z a -t7z {$dir}/archived/{$player}_{$month}.7z {$dir}/{$player}_{$month}*.log -mx=9 -ms=200m -mf -mhc -mhcf -mmt");
        shell_exec("rm -rf {$dir}/{$player}_{$month}*.log");
    }
}
