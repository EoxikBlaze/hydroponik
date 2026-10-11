<?php

use App\Http\Controllers\AmbangController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MejaController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\NotifWaController;
use App\Http\Controllers\PanenController;
use App\Http\Controllers\PendewasaanController;
use App\Http\Controllers\PeremajaanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\SemaiController;
use App\Http\Controllers\SiklusController;
use App\Http\Controllers\TanamanController;
use App\Http\Controllers\TanamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaterControlController;
use Illuminate\Support\Facades\Route;

// ===================================================
// PUBLIC ROUTES
// ===================================================
Route::get('/', [AuthController::class, 'index'])->name('login');
Route::get('/login', [AuthController::class, 'index']);
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// ===================================================
// MONITORING (publik — data & alert)
// ===================================================
Route::get('/monitoring/getReport6Jam', [MonitoringController::class,  'getReport6Jam']);
Route::post('/monitoring/saveAlert', [MonitoringController::class,  'saveAlert']);
Route::post('/monitoring/saveSensor', [MonitoringController::class,  'saveSensor']);

// ===================================================
// AUTH PROTECTED ROUTES
// ===================================================
Route::middleware(['auth', 'demo', 'petani'])->group(function () {

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // TANAM SAYA (alur petani — tahapan otomatis dari master data)
    Route::get('/tanam', [TanamController::class, 'index'])->name('tanam.index');
    Route::post('/tanam/mulai', [TanamController::class, 'mulai'])->name('tanam.mulai');
    Route::post('/tanam/{tipe}/{id}/selesai', [TanamController::class, 'selesai'])->name('tanam.selesai');
    Route::post('/tanam/{id}/hapus', [TanamController::class, 'hapus'])->name('tanam.hapus');

    // MEJA
    Route::resource('meja', MejaController::class);

    // TANAMAN
    Route::resource('tanaman', TanamanController::class);

    // SIKLUS
    // Tarik data diletakkan SEBELUM resource supaya 'siklus/csv' tidak
    // tertangkap oleh pola 'siklus/{siklus}'.
    Route::get('siklus/csv', [SiklusController::class, 'csv'])->name('siklus.csv');
    Route::resource('siklus', SiklusController::class);

    // AMBANG SENSOR (master batas normal tiap sensor)
    Route::resource('ambang', AmbangController::class);

    // SEMAI
    // Detail batch (ikon mata) diletakkan SEBELUM resource supaya pola
    // 'semai/{semai}' tidak menangkap 'semai/batch/44'.
    Route::get('/semai/batch/{id}', [SemaiController::class, 'detail'])->name('semai.batch');
    Route::resource('semai', SemaiController::class);
    Route::get('/semai/selesai', [SemaiController::class, 'dataSelesai'])->name('semai.selesai');

    // PEREMAJAAN
    Route::resource('peremajaan', PeremajaanController::class);

    // PENDEWASAAN
    Route::resource('pendewasaan', PendewasaanController::class);

    // PANEN
    Route::resource('panen', PanenController::class);

    // MONITORING
    Route::resource('monitoring', MonitoringController::class)->only(['index']);
    Route::get('/monitoring/chart-data', [MonitoringController::class, 'getChartData'])->name('monitoring.chart');
    Route::get('/monitoring/tarik', [MonitoringController::class, 'tarik'])->name('monitoring.tarik');
    Route::get('/monitoring/tarik/csv', [MonitoringController::class, 'tarikCsv'])->name('monitoring.tarik.csv');

    // WATER CONTROL
    Route::get('/watercontrol', [WaterControlController::class, 'index'])->name('watercontrol.index');
    Route::post('/watercontrol/fill', [WaterControlController::class, 'fill'])->name('watercontrol.fill');
    Route::post('/watercontrol/stop', [WaterControlController::class, 'stop'])->name('watercontrol.stop');
    Route::get('/watercontrol/status', [WaterControlController::class, 'status'])->name('watercontrol.status');
    Route::post('/watercontrol/setMode', [WaterControlController::class, 'setMode'])->name('watercontrol.mode');

    // LAPORAN
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/csv', [LaporanController::class, 'csv'])->name('laporan.csv');

    // PROFIL
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::post('/profil/update', [ProfilController::class, 'update'])->name('profil.update');
    Route::get('/profil/password', [ProfilController::class, 'formPassword'])->name('profil.password');
    Route::post('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password.update');

    // USER MANAGEMENT
    Route::resource('user', UserController::class);

    // NOTIFIKASI WHATSAPP (Fonnte — API key & nomor tujuan diatur di website)
    Route::get('/notifikasi-wa', [NotifWaController::class, 'index'])->name('notifikasi-wa.index');
    Route::post('/notifikasi-wa', [NotifWaController::class, 'update'])->name('notifikasi-wa.update');
    Route::post('/notifikasi-wa/uji', [NotifWaController::class, 'uji'])->name('notifikasi-wa.uji');
});
