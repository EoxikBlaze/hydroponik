<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    DashboardController,
    MejaController,
    TanamanController,
    SiklusController,
    SemaiController,
    PeremajaanController,
    PendewasaanController,
    PanenController,
    MonitoringController,
    WaterControlController,
    NotifikasiWAController,
    PenerimaNotifController,
    LaporanController,
    ProfilController,
    UserController,
};

// ===================================================
// PUBLIC ROUTES
// ===================================================
Route::get('/',       [AuthController::class, 'index'])->name('login');
Route::get('/login',  [AuthController::class, 'index']);
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// ===================================================
// NOTIFIKASI WA (sebagian butuh auth, sebagian tidak)
// ===================================================
Route::get('/monitoring/sendReportWA',   [MonitoringController::class,  'sendFullReport']);
Route::get('/monitoring/getReport6Jam',  [MonitoringController::class,  'getReport6Jam']);
Route::post('/monitoring/saveAlert',     [MonitoringController::class,  'saveAlert']);
Route::post('/monitoring/saveSensor',    [MonitoringController::class,  'saveSensor']);
Route::get('/monitoring/sendFullReport', [MonitoringController::class,  'sendFullReport']);
Route::get('/cron/check-sensor',         [NotifikasiWAController::class,'sendReport']);

// ===================================================
// AUTH PROTECTED ROUTES
// ===================================================
Route::middleware('auth')->group(function () {

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // MEJA
    Route::resource('meja', MejaController::class);

    // TANAMAN
    Route::resource('tanaman', TanamanController::class);

    // SIKLUS
    Route::resource('siklus', SiklusController::class);

    // SEMAI
    Route::resource('semai', SemaiController::class);
    Route::get('/semai/selesai',              [SemaiController::class, 'dataSelesai'])->name('semai.selesai');
    Route::post('/semai/selesai-proses',      [SemaiController::class, 'selesaiProses'])->name('semai.selesai.proses');
    Route::get('/semai/tanam/{id}',           [SemaiController::class, 'semaiTanam'])->name('semai.tanam');
    Route::post('/semai/tanam-simpan',        [SemaiController::class, 'semaiTanamSimpan'])->name('semai.tanam.simpan');
    Route::post('/semai/get-tanaman-by-siklus', [SemaiController::class, 'getTanamanBySiklus']);

    // PEREMAJAAN
    Route::resource('peremajaan', PeremajaanController::class);

    // PENDEWASAAN
    Route::resource('pendewasaan', PendewasaanController::class);

    // PANEN
    Route::resource('panen', PanenController::class);
    Route::get('/siklus-tanam/{id}/panen',    [PanenController::class, 'panenSiklus']);
    Route::post('/siklus-tanam/create-panen', [PanenController::class, 'createPanenSiklus']);

    // MONITORING
    Route::resource('monitoring', MonitoringController::class)->except(['show']);
    Route::get('/monitoring/chart-data',      [MonitoringController::class, 'getChartData'])->name('monitoring.chart');

    // WATER CONTROL
    Route::get('/watercontrol',               [WaterControlController::class, 'index'])->name('watercontrol.index');
    Route::post('/watercontrol/fill',         [WaterControlController::class, 'fill'])->name('watercontrol.fill');
    Route::post('/watercontrol/stop',         [WaterControlController::class, 'stop'])->name('watercontrol.stop');
    Route::get('/watercontrol/status',        [WaterControlController::class, 'status'])->name('watercontrol.status');
    Route::post('/watercontrol/setMode',      [WaterControlController::class, 'setMode'])->name('watercontrol.mode');

    // PENERIMA NOTIF
    Route::resource('penerima_notif', PenerimaNotifController::class);

    // NOTIFIKASI WA (auth)
    Route::prefix('notifikasi-wa')->name('notifikasi-wa.')->group(function () {
        Route::get('/',            [NotifikasiWAController::class, 'index'])->name('index');
        Route::get('/test',        [NotifikasiWAController::class, 'test'])->name('test');
        Route::get('/send-report', [NotifikasiWAController::class, 'sendReport'])->name('report');
        Route::get('/getThresholds', [NotifikasiWAController::class, 'getThresholds'])->name('thresholds');
    });

    // LAPORAN
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');

    // PROFIL
    Route::get('/profil',           [ProfilController::class, 'index'])->name('profil.index');
    Route::post('/profil/update',   [ProfilController::class, 'update'])->name('profil.update');
    Route::get('/profil/password',  [ProfilController::class, 'formPassword'])->name('profil.password');
    Route::post('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password.update');

    // USER MANAGEMENT
    Route::resource('user', UserController::class);
});
