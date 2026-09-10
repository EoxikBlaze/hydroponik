@extends('layouts.app')
@section('title', 'Kontrol Pengisian Air Tandon')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-faucet-drip me-2 text-primary"></i>Kontrol Pengisian Air Tandon</h4>
        <p class="text-muted small mb-0">Atur pengisian air tandon secara otomatis atau manual dengan mudah</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span id="device-status-badge" class="badge bg-secondary px-3 py-2 fs-6">
            <i class="fas fa-circle-notch fa-spin me-1"></i> Memeriksa Alat...
        </span>
        <span id="mode-badge" class="badge {{ $wc->mode === 'AUTO' ? 'badge-soft-success' : 'badge-soft-warning' }} px-3 py-2 fs-6">
            Mode: {{ $wc->mode }}
        </span>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Visual Status Tandon Air -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 p-4 text-center">
            <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 1px;">Ketinggian Air Tangki</h6>

            <div class="position-relative mx-auto my-3" style="width: 180px; height: 240px; border: 4px solid #cbd5e1; border-radius: 24px; overflow: hidden; background: #f8fafc; box-shadow: inset 0 2px 10px rgba(0,0,0,0.05);">
                <!-- Animated Water Fill -->
                <div id="tank-water" style="position: absolute; bottom: 0; left: 0; right: 0; height: 50%; background: linear-gradient(180deg, #38bdf8 0%, #0284c7 100%); transition: height 1.2s cubic-bezier(0.4, 0, 0.2, 1); opacity: 0.88;">
                    <div style="position: absolute; top: -8px; left: 0; right: 0; height: 16px; background: rgba(255,255,255,0.4); border-radius: 50%;"></div>
                </div>

                <div class="position-absolute top-50 start-50 translate-middle text-white fw-bold" style="z-index: 5;">
                    <i class="fas fa-droplet fa-2x mb-1 d-block text-white" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>
                    <span id="tank-level-text" class="fs-6 text-dark fw-extrabold bg-white bg-opacity-90 px-3 py-1 rounded-pill shadow-sm">
                        LEVEL: MEMUAT...
                    </span>
                </div>
            </div>

            <div class="mt-3">
                <span class="text-muted small d-block">Status Kran Pengisi Air:</span>
                <h4 id="valve-status-text" class="fw-bold {{ $wc->valve_state === 'ON' ? 'text-success' : 'text-secondary' }} mb-0">
                    <i id="valve-status-icon" class="fas {{ $wc->valve_state === 'ON' ? 'fa-circle-play' : 'fa-circle-pause' }} me-1"></i>
                    <span id="valve-text">{{ $wc->valve_state === 'ON' ? 'KRAN SEDANG MENGISI AIR (BUKA)' : 'KRAN SEDANG TUTUP (BERHENTI)' }}</span>
                </h4>
            </div>
        </div>
    </div>

    <!-- Panel Kontrol Aktuator -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-sliders me-2 text-primary"></i>Tombol Buka / Tutup Kran Air</h6>
                <p class="text-muted small">
                    Perintah akan diterima alat di kebun dalam beberapa detik.
                </p>

                <div class="row g-3 my-2">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <span class="text-muted small d-block mb-1">Mode Kerja Sistem</span>
                            <form action="{{ route('watercontrol.mode') }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <select name="mode" class="form-select form-select-sm fw-bold">
                                    <option value="AUTO" {{ $wc->mode === 'AUTO' ? 'selected':'' }}>AUTO (Otomatis)</option>
                                    <option value="MANUAL" {{ $wc->mode === 'MANUAL' ? 'selected':'' }}>MANUAL (Pengguna)</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-dark">Simpan</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 border bg-light">
                            <span class="text-muted small d-block mb-1">Waktu Terakhir Alat Merespons</span>
                            <span id="last-telemetry-time" class="fw-bold text-dark small d-block">-</span>
                            <small id="last-telemetry-ago" class="text-muted" style="font-size: 0.72rem;">Memeriksa status...</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label small fw-bold text-secondary">Atur Kran Secara Manual:</label>
                    <div class="d-flex gap-3 flex-wrap">
                        <form action="{{ route('watercontrol.fill') }}" method="POST" onsubmit="return confirm('Mulai pengisian air sekarang? Pastikan pasokan air utama tersedia.')">
                            @csrf
                            <button type="submit" class="btn btn-emerald px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm">
                                <i class="fas fa-circle-play fs-5"></i>
                                <span>Buka Kran (Mulai Isi)</span>
                            </button>
                        </form>

                        <form action="{{ route('watercontrol.stop') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm">
                                <i class="fas fa-circle-stop fs-5"></i>
                                <span>Tutup Kran (Berhenti)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning border-0 rounded-3 small mt-4 mb-0 d-flex gap-2 align-items-start" style="background-color: #fffbeb; color: #92400e;">
                <i class="fas fa-shield-halved mt-1"></i>
                <div>
                    <strong>Keamanan Pengisian Otomatis:</strong>
                    Kran air otomatis berhenti jika pengisian sudah melebihi 20 menit untuk mencegah air tumpah jika ada pipa bocor.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function pollStatus() {
    try {
        const res = await fetch('/api/latest-sensor');
        const data = await res.json();

        // 1. Status Alat (Online / Offline)
        const badge = document.getElementById('device-status-badge');
        if (data.is_online) {
            badge.className = 'badge badge-soft-success px-3 py-2 fs-6';
            badge.innerHTML = '<i class="fas fa-circle text-success me-1 fa-beat" style="--fa-animation-duration: 1.5s;"></i> ALAT KEBUN AKTIF';
        } else {
            badge.className = 'badge badge-soft-danger px-3 py-2 fs-6';
            badge.innerHTML = '<i class="fas fa-circle text-danger me-1"></i> ALAT KEBUN MATI';
        }

        // 2. Status Solenoid Valve
        const valveOn = (data.water_control.valve_state === 'ON');
        const valveText = document.getElementById('valve-text');
        const valveIcon = document.getElementById('valve-status-icon');
        const valveH4   = document.getElementById('valve-status-text');

        if (valveOn) {
            valveH4.className = 'fw-bold text-success mb-0';
            valveIcon.className = 'fas fa-circle-play me-1';
            valveText.textContent = 'KRAN SEDANG MENGISI AIR (BUKA)';
        } else {
            valveH4.className = 'fw-bold text-secondary mb-0';
            valveIcon.className = 'fas fa-circle-pause me-1';
            valveText.textContent = 'KRAN SEDANG TUTUP (BERHENTI)';
        }

        // 3. Level Air Tangki
        const waterLevel = (data.sensor && data.sensor.water_level) ? data.sensor.water_level.toUpperCase() : 'UNKNOWN';
        const tankWater = document.getElementById('tank-water');
        const tankLevelText = document.getElementById('tank-level-text');

        tankLevelText.textContent = 'LEVEL: ' + waterLevel;
        if (waterLevel.includes('PENUH') || waterLevel.includes('HIGH')) {
            tankWater.style.height = '85%';
            tankWater.style.background = 'linear-gradient(180deg, #10b981 0%, #059669 100%)';
        } else if (waterLevel.includes('RENDAH') || waterLevel.includes('LOW')) {
            tankWater.style.height = '25%';
            tankWater.style.background = 'linear-gradient(180deg, #f87171 0%, #dc2626 100%)';
        } else {
            tankWater.style.height = '55%';
            tankWater.style.background = 'linear-gradient(180deg, #38bdf8 0%, #0284c7 100%)';
        }

        // 4. Waktu Terakhir
        if (data.sensor && data.sensor.created_at) {
            const date = new Date(data.sensor.created_at);
            document.getElementById('last-telemetry-time').textContent = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('last-telemetry-ago').textContent = Math.round(data.seconds_ago) + ' detik yang lalu';
        }

    } catch (err) {
        console.error('Error polling status:', err);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    pollStatus();
    setInterval(pollStatus, 3000); // Polling tiap 3 detik
});
</script>
@endpush
