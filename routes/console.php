<?php

use Illuminate\Support\Facades\Schedule;

// Bersihkan file temporary & ZIP lama setiap hari.
Schedule::command('photos:cleanup')->dailyAt('03:00')->withoutOverlapping();

// Hapus record job queue gagal yang sudah lama.
Schedule::command('queue:prune-failed --hours=168')->daily();
