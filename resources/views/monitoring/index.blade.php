@extends('layouts.app')
@section('title', 'Monitoring Sensor Real-Time')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-satellite-dish me-2 text-primary"></i>Monitoring Sensor IoT Real-Time</h4>
        <p class="text-muted small mb-0">Telemetri sensor mikroklimat & larutan nutrisi greenhouse HarvestHouse</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div id="sync-badge" class="topbar-badge-offline">
            <span id="sync-dot" class="offline-dot"></span>
            <span id="sync-status">Mengecek Koneksi...</span>
        </div>
        <button id="btn-refresh" class="btn btn-sm btn-light border px-3" onclick="loadChartData(true)">
            <i class="fas fa-rotate me-1" id="refresh-icon"></i> Refresh
        </button>
    </div>
</div>

<!-- REAL OFFLINE WARNING ALERT (DINAMIS VIA JS) -->
<div id="offline-alert" class="alert alert-danger border-0 shadow-sm rounded-3 py-3 mb-4 d-none" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #ef4444 !important;">
    <div class="d-flex align-items-center gap-3">
        <i class="fas fa-tower-broadcast fa-2x text-danger opacity-75"></i>
        <div>
            <h6 class="fw-bold mb-1" id="offline-title">ESP32 Tidak Mengirimkan Data (Sinkronisasi Mati)</h6>
            <div class="small opacity-90" id="offline-desc">
                Tidak ada data baru masuk dalam 5 menit terakhir. Nilai dan grafik di bawah menampilkan <strong>rekaman historis terakhir</strong>.
            </div>
        </div>
    </div>
</div>

<!-- LIVE TELEMETRY ROW -->
<div class="row g-3 mb-4">
    <!-- Suhu Air -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated border-0 shadow-sm h-100" style="border-top: 4px solid #0284c7 !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-muted">Suhu Air</span>
                    <i class="fas fa-temperature-half text-info fs-5"></i>
                </div>
                <h2 class="fw-bold mb-1 text-dark" id="val-waterTemp">--</h2>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">°C</small>
                    <span class="badge badge-soft-danger" id="badge-waterTemp">Mati</span>
                </div>
            </div>
        </div>
    </div>

    <!-- pH Air -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated border-0 shadow-sm h-100" style="border-top: 4px solid #8b5cf6 !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-muted">pH Nutrisi</span>
                    <i class="fas fa-flask text-purple fs-5" style="color: #8b5cf6;"></i>
                </div>
                <h2 class="fw-bold mb-1 text-dark" id="val-ph">--</h2>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">5.5 - 6.5</small>
                    <span class="badge badge-soft-danger" id="badge-ph">Mati</span>
                </div>
            </div>
        </div>
    </div>

    <!-- TDS Nutrisi -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated border-0 shadow-sm h-100" style="border-top: 4px solid #d97706 !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-muted">TDS Nutrisi</span>
                    <i class="fas fa-bolt text-warning fs-5"></i>
                </div>
                <h2 class="fw-bold mb-1 text-dark" id="val-tds">--</h2>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">ppm</small>
                    <span class="badge badge-soft-danger" id="badge-tds">Mati</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Suhu Udara -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated border-0 shadow-sm h-100" style="border-top: 4px solid #ea580c !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-muted">Suhu Udara</span>
                    <i class="fas fa-sun text-danger fs-5"></i>
                </div>
                <h2 class="fw-bold mb-1 text-dark" id="val-airTemp">--</h2>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">°C</small>
                    <span class="badge badge-soft-danger" id="badge-airTemp">Mati</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Kelembapan -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated border-0 shadow-sm h-100" style="border-top: 4px solid #0d9488 !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-muted">Kelembapan</span>
                    <i class="fas fa-droplet text-success fs-5"></i>
                </div>
                <h2 class="fw-bold mb-1 text-dark" id="val-humidity">--</h2>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">% RH</small>
                    <span class="badge badge-soft-danger" id="badge-humidity">Mati</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Level Air -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-elevated border-0 shadow-sm h-100" style="border-top: 4px solid #059669 !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-muted">Level Tandon</span>
                    <i class="fas fa-water text-primary fs-5"></i>
                </div>
                <h2 class="fw-bold mb-1 text-dark" id="val-waterLevel">--</h2>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Status</small>
                    <span class="badge badge-soft-danger" id="badge-waterLevel">Mati</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- GRAFIK CHART SECTION -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-chart-area text-primary fs-5"></i>
            <h6 class="fw-bold mb-0 text-dark">Grafik Tren Sensor (Data Riil Tercatat)</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-sm-inline">Auto-update:</span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary active" onclick="setRefreshInterval(5000, this)">5d</button>
                <button type="button" class="btn btn-outline-secondary" onclick="setRefreshInterval(15000, this)">15d</button>
                <button type="button" class="btn btn-outline-secondary" onclick="setRefreshInterval(0, this)">Pause</button>
            </div>
        </div>
    </div>
    <div class="card-body p-3 p-md-4">
        <div id="chart-empty-state" class="text-center py-5 d-none">
            <i class="fas fa-chart-line fa-3x text-muted mb-3 d-block opacity-40"></i>
            <h6 class="fw-bold text-dark">Belum Ada Data Sensor Tercatat</h6>
            <p class="text-muted small mb-0">Hubungkan mikrokontroler ESP32 ke endpoint API untuk mulai merekam telemetri.</p>
        </div>
        <div id="chart-wrapper" style="position: relative; height: 360px; width: 100%;">
            <canvas id="sensorChart"></canvas>
        </div>
    </div>
</div>

<!-- PANDUAN THRESHOLD SAYURAN -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent py-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-book-open me-2 text-success"></i>Standar Parameter Nutrisi & Lingkungan Hidroponik</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Komoditas / Parameter</th>
                    <th>pH Ideal</th>
                    <th>TDS Nutrisi (ppm)</th>
                    <th>Suhu Air (°C)</th>
                    <th>Kelembapan (%)</th>
                    <th>Status Toleransi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="ps-4 fw-bold">🥬 Selada (Lactuca sativa)</td>
                    <td><span class="badge badge-soft-success">5.5 - 6.5</span></td>
                    <td>560 - 840 ppm</td>
                    <td>20 - 28 °C</td>
                    <td>60 - 75 %</td>
                    <td><span class="badge badge-soft-success">Toleransi Baik</span></td>
                </tr>
                <tr>
                    <td class="ps-4 fw-bold">🌿 Seledri (Apium graveolens)</td>
                    <td><span class="badge badge-soft-success">6.0 - 6.8</span></td>
                    <td>1200 - 1600 ppm</td>
                    <td>18 - 25 °C</td>
                    <td>70 - 85 %</td>
                    <td><span class="badge badge-soft-info">Butuh Kelembapan</span></td>
                </tr>
                <tr>
                    <td class="ps-4 fw-bold">🌱 Sayuran Daun Umum</td>
                    <td><span class="badge badge-soft-success">5.5 - 6.5</span></td>
                    <td>800 - 1200 ppm</td>
                    <td>22 - 29 °C</td>
                    <td>60 - 80 %</td>
                    <td><span class="badge badge-soft-primary">Standar Greenhouse</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
let sensorChart = null;
let refreshTimer = null;
let currentInterval = 5000;

async function loadChartData(isManual = false) {
    const refreshIcon = document.getElementById('refresh-icon');
    if (isManual && refreshIcon) refreshIcon.classList.add('fa-spin');

    try {
        const response = await fetch('{{ route("monitoring.chart") }}');
        const data = await response.json();

        const syncBadge    = document.getElementById('sync-badge');
        const syncDot      = document.getElementById('sync-dot');
        const syncStatus   = document.getElementById('sync-status');
        const offlineAlert = document.getElementById('offline-alert');
        const offlineDesc  = document.getElementById('offline-desc');

        if (!data || data.length === 0) {
            // TIDAK ADA DATA SAMA SEKALI
            syncBadge.className = 'topbar-badge-offline';
            syncDot.className   = 'offline-dot';
            syncStatus.textContent = 'Sinkronisasi Mati (Tidak Ada Data)';
            offlineAlert.classList.remove('d-none');
            offlineDesc.innerHTML = 'Belum pernah ada data sensor yang masuk ke database.';
            document.getElementById('chart-empty-state').classList.remove('d-none');
            document.getElementById('chart-wrapper').classList.add('d-none');
            return;
        }

        document.getElementById('chart-empty-state').classList.add('d-none');
        document.getElementById('chart-wrapper').classList.remove('d-none');

        const latest = data[data.length - 1];

        // Hitung selisih waktu dari data terakhir ke waktu sekarang
        const latestTime = new Date(latest.created_at).getTime();
        const now = new Date().getTime();
        const diffMinutes = (now - latestTime) / (1000 * 60);

        const isOnline = diffMinutes <= 5;

        if (isOnline) {
            syncBadge.className = 'topbar-badge-live';
            syncDot.className   = 'live-dot';
            syncStatus.textContent = 'Sinkronisasi Aktif (Live)';
            offlineAlert.classList.add('d-none');
        } else {
            syncBadge.className = 'topbar-badge-offline';
            syncDot.className   = 'offline-dot';

            let waktuLaluText = '';
            if (diffMinutes < 60) {
                waktuLaluText = Math.floor(diffMinutes) + ' menit yang lalu';
            } else if (diffMinutes < 1440) {
                waktuLaluText = Math.floor(diffMinutes / 60) + ' jam yang lalu';
            } else {
                waktuLaluText = Math.floor(diffMinutes / 1440) + ' hari yang lalu';
            }

            syncStatus.textContent = 'Sinkronisasi Terputus (' + waktuLaluText + ')';
            offlineAlert.classList.remove('d-none');
            offlineDesc.innerHTML = 'Tidak ada sinyal data dalam 5 menit terakhir. Data terakhir diterima pada <strong>' + new Date(latest.created_at).toLocaleString('id-ID') + '</strong> (' + waktuLaluText + ').';
        }

        // Update nilai telemetri kartu
        document.getElementById('val-waterTemp').textContent  = latest.waterTemp !== null ? (latest.waterTemp + '°') : '--';
        document.getElementById('val-ph').textContent         = latest.ph !== null ? latest.ph : '--';
        document.getElementById('val-tds').textContent        = latest.tds !== null ? latest.tds : '--';
        document.getElementById('val-airTemp').textContent    = latest.airTemp !== null ? (latest.airTemp + '°') : '--';
        document.getElementById('val-humidity').textContent   = latest.humidity !== null ? (latest.humidity + '%') : '--';
        document.getElementById('val-waterLevel').textContent = latest.water_level ?? '--';

        // Update badges status kartu
        const badgeState = isOnline ? 'Live' : 'Mati';
        const badgeClass = isOnline ? 'badge-soft-success' : 'badge-soft-danger';

        ['waterTemp', 'ph', 'tds', 'airTemp', 'humidity', 'waterLevel'].forEach(id => {
            const el = document.getElementById('badge-' + id);
            if (el) {
                el.className = 'badge ' + badgeClass;
                el.textContent = badgeState;
            }
        });

        // Update Grafik Garis
        const labels = data.map(d => {
            const dt = new Date(d.created_at);
            return dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        });

        const ctx = document.getElementById('sensorChart').getContext('2d');

        const datasets = [
            {
                label: 'pH Nutrisi',
                data: data.map(d => d.ph),
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.08)',
                tension: 0.35,
                fill: true,
                borderWidth: 2.5,
                yAxisID: 'yPH'
            },
            {
                label: 'Suhu Air (°C)',
                data: data.map(d => d.waterTemp),
                borderColor: '#0284c7',
                backgroundColor: 'rgba(2, 132, 199, 0.08)',
                tension: 0.35,
                fill: false,
                borderWidth: 2,
                yAxisID: 'yTemp'
            },
            {
                label: 'TDS (ppm)',
                data: data.map(d => d.tds),
                borderColor: '#d97706',
                backgroundColor: 'rgba(217, 119, 6, 0.08)',
                tension: 0.35,
                fill: false,
                borderWidth: 2,
                yAxisID: 'yTDS'
            },
            {
                label: 'Suhu Udara (°C)',
                data: data.map(d => d.airTemp),
                borderColor: '#ea580c',
                tension: 0.35,
                fill: false,
                borderWidth: 1.5,
                borderDash: [4, 4],
                yAxisID: 'yTemp'
            },
            {
                label: 'Kelembapan (%)',
                data: data.map(d => d.humidity),
                borderColor: '#0d9488',
                tension: 0.35,
                fill: false,
                borderWidth: 1.5,
                yAxisID: 'yTemp'
            }
        ];

        if (sensorChart) {
            sensorChart.data.labels = labels;
            sensorChart.data.datasets = datasets;
            sensorChart.update('none');
        } else {
            sensorChart = new Chart(ctx, {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, cornerRadius: 8 }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        yTemp: {
                            type: 'linear',
                            position: 'left',
                            title: { display: true, text: 'Suhu (°C) / Kelembapan (%)' },
                            grid: { color: 'rgba(226, 232, 240, 0.6)' }
                        },
                        yPH: {
                            type: 'linear',
                            position: 'right',
                            min: 0,
                            max: 14,
                            title: { display: true, text: 'pH' },
                            grid: { drawOnChartArea: false }
                        },
                        yTDS: {
                            type: 'linear',
                            position: 'right',
                            display: false,
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
        }
    } catch (e) {
        console.error('Gagal memuat data sensor:', e);
    } finally {
        if (isManual && refreshIcon) {
            setTimeout(() => refreshIcon.classList.remove('fa-spin'), 600);
        }
    }
}

function setRefreshInterval(ms, btn) {
    if (btn) {
        btn.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }
    clearInterval(refreshTimer);
    currentInterval = ms;
    if (ms > 0) {
        refreshTimer = setInterval(loadChartData, ms);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadChartData();
    refreshTimer = setInterval(loadChartData, currentInterval);
});
</script>
@endpush
