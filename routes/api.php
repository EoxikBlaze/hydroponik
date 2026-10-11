<?php

use App\Http\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;

// Endpoint publik untuk Web Frontend Polling (Real-time monitoring & valve status)
Route::get('/latest-sensor', [MonitoringController::class, 'latestSensor']);

// ===================================================
// API UNTUK ESP32 — Dilindungi API Key Middleware
// ===================================================
Route::middleware('api.key')->group(function () {
    Route::get('/get-thresholds', [MonitoringController::class, 'getThresholdsForESP32']);
    Route::post('/save-sensor', [MonitoringController::class, 'saveSensor']);
    Route::post('/notif-hardware', [MonitoringController::class, 'receiveFromESP32']);
});
