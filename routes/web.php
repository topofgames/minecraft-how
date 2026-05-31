<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Support\MinecraftServerStatus;
use Inertia\Inertia;

$servers = [
    [
        'id' => 1,
        'name' => 'Hypixel Network',
        'host' => 'mc.hypixel.net',
        'port' => 25565,
        'version' => '1.8 - 1.21',
        'players' => 0,
        'maxPlayers' => 0,
        'mode' => 'Minigames',
        'status' => 'online',
        'country' => 'US',
        'description' => 'Large public network with minigames and seasonal events.',
    ],
    [
        'id' => 2,
        'name' => 'CubeCraft Games',
        'host' => 'play.cubecraft.net',
        'port' => 25565,
        'version' => '1.8 - 1.21',
        'players' => 0,
        'maxPlayers' => 0,
        'mode' => 'Minigames',
        'status' => 'online',
        'country' => 'EU',
        'description' => 'Public minigames network with live MOTD colors.',
    ],
    [
        'id' => 3,
        'name' => 'Minehut',
        'host' => 'play.minehut.com',
        'port' => 25565,
        'version' => '1.8 - 1.21',
        'players' => 0,
        'maxPlayers' => 0,
        'mode' => 'SMP',
        'status' => 'online',
        'country' => 'US',
        'description' => 'Server hosting network with public player worlds.',
    ],
    [
        'id' => 4,
        'name' => 'PikaNetwork',
        'host' => 'play.pika-network.net',
        'port' => 25565,
        'version' => '1.8 - 1.21',
        'players' => 0,
        'maxPlayers' => 0,
        'mode' => 'Survival',
        'status' => 'online',
        'country' => 'EU',
        'description' => 'Public cracked network with Skyblock, Survival and PvP modes.',
    ],
];

Route::get('/', function () use ($servers) {
    return Inertia::render('server-list', ['servers' => $servers]);
})->name('home');

Route::get('/api/servers/status', function (MinecraftServerStatus $status) use ($servers) {
    return collect($servers)->map(function (array $server) use ($status) {
        $key = "minecraft-status:{$server['host']}:{$server['port']}";

        try {
            $live = Cache::remember($key, 60, fn () => $status->ping($server['host'], $server['port']));
        } catch (\Throwable $exception) {
            $live = [
                'online' => false,
                'latency' => null,
                'error' => $exception->getMessage(),
                'motd' => [
                    'plain' => '',
                    'segments' => [],
                ],
            ];
        }

        return [
            'id' => $server['id'],
            ...$live,
        ];
    })->values();
})->name('servers.status');
