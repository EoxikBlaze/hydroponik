<?php

use Illuminate\Support\Facades\Schedule;

// Hapus data sementara akun demo yang sudah lewat 1 jam
Schedule::command('demo:purge')->everyFiveMinutes();

// Pengingat H-1 jadwal pemindahan meja (sekali sehari).
// CATATAN: baris ini HANYA jalan jika hosting punya cron `schedule:run`.
// Karena panel BEKANTANJANTAN dipakai tanpa cron scheduler, jalur utama
// pengingat jadwal adalah piggyback di POST /api/save-sensor (lihat sana).
Schedule::command('jadwal:h1')->dailyAt('07:00')->timezone('Asia/Makassar');

// Ringkasan rata-rata sensor -> WA, jam07:00 WITA (jalur utama: piggyback
// di POST /api/save-sensor karena panel hosting tanpa cron schedule:run).
Schedule::command('sensor:ringkasan')->dailyAt('07:00')->timezone('Asia/Makassar');
