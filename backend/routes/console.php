<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agent 心跳检测：每分钟检查一次，120 秒无心跳则标记离线
Schedule::command('agents:check-heartbeats --timeout=120')
    ->everyMinute()
    ->withoutOverlapping();
