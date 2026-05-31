<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about', function () {
    $this->comment('Minecraft Server List');
})->purpose('Show application info');
