@extends('layouts.app')
@section('title', 'Dashboard Smart Hydroponic')

@section('content')
<!-- HERO GREETING -->
<div class="card border-0 mb-4 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #0b1329 0%, #064e3b 60%, #059669 100%); border-radius: 18px; box-shadow: 0 10px 30px -10px rgba(5, 150, 105, 0.35);">
    <div class="card-body p-4 p-md-5 position-relative z-1">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-white bg-opacity-25 text-white mb-2 px-3 py-1 rounded-pill small">
                    <i class="fas fa-leaf me-1"></i> Greenhouse Politala • HarvestHouse IoT
                </span>
                <h2 class="fw-bold mb-2" style="letter-spacing: -0.5px;">Selamat Datang di HarvestHouse</h2>
                <p class="text-white text-opacity-80 mb-4" style="max-width: 600px;">
                    Sistem pemantauan lingkungan mikroklimat nutrisi hidroponik berbasis ESP32 secara real-time, manajemen siklus tanam, dan otomatisasi peringatan WhatsApp.
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('monitoring.index') }}" class="btn btn-light px-4 py-2 fw-semibold rounded-3 text-dark shadow-sm">
                        <i class="fas fa-satellite-dish me-2 text-success"></i>Lihat Grafik Sensor
                    </a>
                    <a href="{{ route('semai.create') }}" class="btn btn-outline-light px-4 py-2 fw-semibold rounded-3">
                        <i class="fas fa-plus-circle me-2"></i>Mulai Semai Baru
                    </a>
                    <a href="{{ route('watercontrol.index') }}" class="btn btn-outline-light px-3 py-2 fw-semibold rounded-3">
                        <i class="fas fa-faucet-drip me-2"></i>Water Control
                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block text-end">
                <img src="{{ asset('images/logo.png') }}" style="max-height: 150px; background: #ffffff; border-radius: 24px; padding: 8px; filter: drop-shadow(0 15px 25px rgba(0,0,0,0.3));" alt="HarvestHouse" style="max-height: 140px; filter: drop-shadow(0 15px 25px rgba(0,0,0,0.3));" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</div>

<!-- SECTION: REAL STATUS TELEMETRI SENSOR IOT -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0 text-dark">
            <i class="fas fa-microchip me-2 text-primary"></i>Telemetri Sensor Terkini (IoT ESP32)
        </h5>
        <small class="text-muted">
            @if(!empty($iotStatus) && $iotStatus['online'])
                <span class="text-success fw-semibold"><i class="fas fa-circle-check me-1"></i>Koneksi Aktif</span> • Data realtime sedang mengalir
            @else
                <span class="text-danger fw-semibold"><i class="fas fa-circle-xmark me-1"></i>Sinkronisasi Mati</span> • Terakhir data diterima: <strong>{{ $iotStatus['diff_text'] ?? 'Belum ada data' }}</strong>
            @endif
        </small>
    </div>
    <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-light border small fw-semibold text-secondary">
        Detail Grafik <i class="fas fa-arrow-right ms-1"></i>
    </a>
</div>

<!-- REAL OFFLINE WARNING BANNER (JIKA ESP32 MATI/TIDAK MENGIRIM DATA) -->
@if(empty($iotStatus) || !$iotStatus['online'])
<div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center justify-content-between py-3 mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #ef4444 !important;">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-white p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; color: #ef4444;">
            <i class="fas fa-triangle-exclamation fs-5"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-1">Perangkat ESP32 Tidak Mengirimkan Data (Sinkronisasi Mati)</h6>
            <div class="small opacity-90">
                Tidak ada data sensor masuk dalam 5 menit terakhir.
                @if($latest_sensor)
                    Nilai di bawah adalah <strong>rekaman historis terakhir</strong> pada <strong>{{ $latest_sensor->created_at->format('d/m/Y H:i:s') }} WIB</strong> ({{ $iotStatus['diff_text'] }}).
                @else
                    Belum pernah ada data telemetri yang disimpan di sistem.
                @endif
            </div>
        </div>
    </div>
    <span class="badge bg-danger px-3 py-2 text-uppercase d-none d-md-inline-block">ESP32 Offline</span>
</div>
@endif

<div class="row g-3 mb-4">
    <!-- pH Card -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated h-100 border-0 shadow-sm" style="border-top: 4px solid #8b5cf6 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-muted" title="Tingkat Asam-Basa Air Nutrisi">pH (Asam-Basa)</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #f5f3ff; color: #8b5cf6;">
                        <i class="fas fa-flask"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-dark">
                    {{ $latest_sensor && $latest_sensor->ph !== null ? number_format($latest_sensor->ph, 2) : '--' }}
                </h3>
                <div class="d-flex align-items-center justify-content-between">
                    <small class="text-muted">Opt: 5.5 - 6.5</small>
                    @if(!empty($iotStatus) && $iotStatus['online'])
                        <span class="badge badge-soft-success">Live</span>
                    @else
                        <span class="badge badge-soft-danger">Mati</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Suhu Air -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated h-100 border-0 shadow-sm" style="border-top: 4px solid #0284c7 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-muted" title="Suhu Air di Tandon Penampungan">Suhu Air Tandon</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #f0f9ff; color: #0284c7;">
                        <i class="fas fa-temperature-half"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-dark">
                    @if($latest_sensor && $latest_sensor->waterTemp !== null)
                        {{ number_format($latest_sensor->waterTemp, 1) }} <span class="fs-6 fw-normal text-muted">°C</span>
                    @else
                        --
                    @endif
                </h3>
                <div class="d-flex align-items-center justify-content-between">
                    <small class="text-muted">Opt: 20 - 30°C</small>
                    @if(!empty($iotStatus) && $iotStatus['online'])
                        <span class="badge badge-soft-info">Live</span>
                    @else
                        <span class="badge badge-soft-danger">Mati</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- TDS Nutrisi -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated h-100 border-0 shadow-sm" style="border-top: 4px solid #d97706 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-muted" title="Kepekatan Pupuk Tanaman (Parts Per Million)">TDS (Kepekatan Pupuk)</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #fffbeb; color: #d97706;">
                        <i class="fas fa-bolt"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-dark">
                    @if($latest_sensor && $latest_sensor->tds !== null)
                        {{ number_format($latest_sensor->tds) }} <span class="fs-6 fw-normal text-muted">ppm</span>
                    @else
                        --
                    @endif
                </h3>
                <div class="d-flex align-items-center justify-content-between">
                    <small class="text-muted">Target: ~800-1000</small>
                    @if(!empty($iotStatus) && $iotStatus['online'])
                        <span class="badge badge-soft-warning">Live</span>
                    @else
                        <span class="badge badge-soft-danger">Mati</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Suhu Udara -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated h-100 border-0 shadow-sm" style="border-top: 4px solid #ea580c !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-muted" title="Suhu Ruangan Sekitar Tanaman">Suhu Ruangan</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #fff7ed; color: #ea580c;">
                        <i class="fas fa-sun"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-dark">
                    @if($latest_sensor && $latest_sensor->airTemp !== null)
                        {{ number_format($latest_sensor->airTemp, 1) }} <span class="fs-6 fw-normal text-muted">°C</span>
                    @else
                        --
                    @endif
                </h3>
                <div class="d-flex align-items-center justify-content-between">
                    <small class="text-muted">Greenhouse</small>
                    @if(!empty($iotStatus) && $iotStatus['online'])
                        <span class="badge badge-soft-warning">Live</span>
                    @else
                        <span class="badge badge-soft-danger">Mati</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Kelembapan Udara -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated h-100 border-0 shadow-sm" style="border-top: 4px solid #0d9488 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-muted" title="Kelembapan Udara Greenhouse">Kelembapan Udara</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #f0fdfa; color: #0d9488;">
                        <i class="fas fa-droplet"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-dark">
                    @if($latest_sensor && $latest_sensor->humidity !== null)
                        {{ number_format($latest_sensor->humidity, 1) }} <span class="fs-6 fw-normal text-muted">%</span>
                    @else
                        --
                    @endif
                </h3>
                <div class="d-flex align-items-center justify-content-between">
                    <small class="text-muted">Ideal 60-80%</small>
                    @if(!empty($iotStatus) && $iotStatus['online'])
                        <span class="badge badge-soft-success">Live</span>
                    @else
                        <span class="badge badge-soft-danger">Mati</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Level Air Tandon -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated h-100 border-0 shadow-sm" style="border-top: 4px solid #059669 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-muted">Tandon Air</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #ecfdf5; color: #059669;">
                        <i class="fas fa-water"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-dark">
                    {{ $latest_sensor->water_level ?? '--' }}
                </h3>
                <div class="d-flex align-items-center justify-content-between">
                    <small class="text-muted">Katup: {{ $water_control->command ?? 'OFF' }}</small>
                    <span class="badge badge-soft-success">{{ $water_control->mode ?? 'AUTO' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION: ALUR TAHAPAN SIKLUS TANAM -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-timeline me-2 text-success"></i>Pipeline Siklus Tanam Hidroponik</h5>
            <small class="text-muted">Proses berjenjang pertumbuhan sayuran dari benih hingga panen</small>
        </div>
        <span class="badge bg-light text-dark border px-3 py-1">Total {{ $total_tanaman }} Varietas Terdaftar</span>
    </div>

    <div class="row g-3">
        <!-- Step 1: Semai -->
        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('semai.index') }}" class="text-decoration-none">
                <div class="card card-elevated border-0 shadow-sm h-100 p-3" style="background: #ffffff;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="fas fa-seedling fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-muted small fw-bold text-uppercase d-block">Tahap 1</span>
                            <h5 class="fw-bold text-dark mb-0">Penyemaian</h5>
                            <small class="text-success fw-semibold">{{ $total_semai }} Batch Terdata</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Step 2: Peremajaan -->
        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('peremajaan.index') }}" class="text-decoration-none">
                <div class="card card-elevated border-0 shadow-sm h-100 p-3" style="background: #ffffff;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="fas fa-spa fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-muted small fw-bold text-uppercase d-block">Tahap 2</span>
                            <h5 class="fw-bold text-dark mb-0">Peremajaan</h5>
                            <small class="text-warning fw-semibold">{{ $total_peremajaan }} Batch Aktif</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Step 3: Pendewasaan -->
        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('pendewasaan.index') }}" class="text-decoration-none">
                <div class="card card-elevated border-0 shadow-sm h-100 p-3" style="background: #ffffff;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                            <i class="fas fa-leaf fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-muted small fw-bold text-uppercase d-block">Tahap 3</span>
                            <h5 class="fw-bold text-dark mb-0">Pendewasaan</h5>
                            <small class="text-primary fw-semibold">{{ $total_pendewasaan }} Batch Siap Panen</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Step 4: Panen -->
        <div class="col-lg-3 col-sm-6">
            <a href="{{ route('panen.index') }}" class="text-decoration-none">
                <div class="card card-elevated border-0 shadow-sm h-100 p-3" style="background: #ffffff;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 text-white" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                            <i class="fas fa-wheat-awn fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="text-muted small fw-bold text-uppercase d-block">Tahap 4</span>
                            <h5 class="fw-bold text-dark mb-0">Hasil Panen</h5>
                            <small class="text-purple fw-semibold" style="color: #8b5cf6;">{{ $total_panen }} Panen Sukses</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- SECTION: DETAILS & ACTIVITY -->
<div class="row g-4">
    <!-- Rata-Rata Sensor 6 Jam (Hanya tampil jika ada data riil) -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-chart-line text-primary"></i>
                    <h6 class="fw-bold mb-0">Rata-Rata Sensor 6 Jam Terakhir</h6>
                </div>
                <a href="{{ route('notifikasi-wa.report') }}" target="_blank" class="btn btn-sm btn-outline-success">
                    <i class="fab fa-whatsapp me-1"></i>Kirim Laporan WA
                </a>
            </div>
            <div class="card-body p-0">
                @if(!empty($sensor_avg))
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-4 text-muted"><i class="fas fa-flask text-purple me-2"></i>pH Rata-rata</td>
                                    <td class="fw-bold text-end pe-4">{{ $sensor_avg['ph'] !== null ? number_format($sensor_avg['ph'], 2) : '--' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 text-muted"><i class="fas fa-temperature-half text-info me-2"></i>Suhu Air Rata-rata</td>
                                    <td class="fw-bold text-end pe-4">{{ $sensor_avg['waterTemp'] !== null ? number_format($sensor_avg['waterTemp'], 1) . ' °C' : '--' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 text-muted"><i class="fas fa-bolt text-warning me-2"></i>TDS Nutrisi Rata-rata</td>
                                    <td class="fw-bold text-end pe-4">{{ $sensor_avg['tds'] !== null ? number_format($sensor_avg['tds']) . ' ppm' : '--' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 text-muted"><i class="fas fa-sun text-danger me-2"></i>Suhu Udara Rata-rata</td>
                                    <td class="fw-bold text-end pe-4">{{ $sensor_avg['airTemp'] !== null ? number_format($sensor_avg['airTemp'], 1) . ' °C' : '--' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-4 text-muted"><i class="fas fa-droplet text-primary me-2"></i>Kelembapan Rata-rata</td>
                                    <td class="fw-bold text-end pe-4">{{ $sensor_avg['humidity'] !== null ? number_format($sensor_avg['humidity'], 1) . ' %' : '--' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 px-3">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; color: #94a3b8;">
                            <i class="fas fa-satellite-dish fs-4"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Tidak Ada Data dalam 6 Jam Terakhir</h6>
                        <p class="text-muted small mb-0">ESP32 belum mengirimkan telemetri baru pada periode ini. Rata-rata otomatis dihitung saat data sensor masuk.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Alert & Status IoT -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-triangle-exclamation text-warning"></i>
                    <h6 class="fw-bold mb-0">Catatan Peringatan Sensor (Alert Log)</h6>
                </div>
                <span class="badge bg-light text-muted border">Otomatisasi ESP32</span>
            </div>
            <div class="card-body p-3">
                @if($recent_alerts->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($recent_alerts as $alert)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-3 border-bottom">
                            <div>
                                <strong class="d-block text-dark">{{ ucfirst($alert->type) }}</strong>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($alert->created_at)->diffForHumans() }}</small>
                            </div>
                            <span class="badge badge-soft-danger px-3 py-2 fs-6">{{ $alert->value }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5 px-3">
                        <div class="rounded-circle bg-emerald text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; background: #10b981;">
                            <i class="fas fa-check fs-4"></i>
                        </div>
                        <h6 class="fw-bold text-dark">Tidak Ada Peringatan Anomali</h6>
                        <p class="text-muted small mb-0">Seluruh parameter sensor berada dalam toleransi normal atau belum ada alert baru yang dicatat.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
