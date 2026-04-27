<?php

// routes/console.php
// Tambahkan schedule berikut untuk menjalankan auto-selesai setiap hari tengah malam

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule as ScheduleFacade;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Auto-selesaikan penyewaan yang sudah melewati deadline ──────────────────
ScheduleFacade::command('penyewaan:auto-selesai')
    ->dailyAt('00:05')   // Jalankan setiap hari jam 00:05
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/auto-selesai.log'));