<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{NotifikasiWAController, MonitoringController};

// Endpoint publik untuk Web Frontend Polling (Real-time monitoring & valve status)
Route::get('/latest-sensor', [MonitoringController::class, 'latestSensor']);

// ===================================================
// API UNTUK ESP32 — Dilindungi API Key Middleware
// ===================================================
Route::middleware('api.key')->group(function () {
    Route::post('/notif-hardware',  [NotifikasiWAController::class, 'receiveFromESP32']);
    Route::get('/get-thresholds',   [NotifikasiWAController::class, 'getThresholdsForESP32']);
    Route::post('/save-sensor',     [MonitoringController::class,   'saveSensor']);
});
