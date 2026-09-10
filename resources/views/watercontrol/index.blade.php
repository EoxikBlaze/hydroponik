@extends('layouts.app')
@section('title', 'Kontrol Pengisian Air Tandon')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-faucet-drip me-2 text-primary"></i>Kontrol Otomasi Tandon & Solenoid</h4>
        <p class="text-muted small mb-0">Manajemen pengisian air tandon nutrisi hidroponik berbasis aktuator solenoid</p>
    </div>
    <span class="badge {{ $wc->mode === 'AUTO' ? 'badge-soft-success' : 'badge-soft-warning' }} px-3 py-2 fs-6">
        Mode Aktif: {{ $wc->mode }}
    </span>
</div>

<div class="row g-4 mb-4">
    <!-- Visual Status Tandon Air -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100 p-4 text-center">
            <h6 class="fw-bold text-muted text-uppercase mb-4" style="font-size: 0.8rem; letter-spacing: 1px;">Status Tangki & Level Air</h6>

            <div class="position-relative mx-auto my-3" style="width: 170px; height: 230px; border: 4px solid #cbd5e1; border-radius: 20px; overflow: hidden; background: #f8fafc;">
                <!-- Animated Water Fill -->
                @php
                    $fillPercent = match($wc->command) {
                        'FILL' => '85%',
                        'STOP' => '65%',
                        default => '50%'
                    };
                @endphp
                <div style="position: absolute; bottom: 0; left: 0; right: 0; height: {{ $fillPercent }}; background: linear-gradient(180deg, #38bdf8 0%, #0284c7 100%); transition: height 1s ease; opacity: 0.85;">
                    <div style="position: absolute; top: -10px; left: 0; right: 0; height: 20px; background: rgba(255,255,255,0.3); border-radius: 50%;"></div>
                </div>

                <div class="position-absolute top-50 start-50 translate-middle text-white fw-bold text-shadow" style="z-index: 5;">
                    <i class="fas fa-droplet fa-2x mb-1 d-block text-white"></i>
                    <span class="fs-5 text-dark fw-extrabold bg-white bg-opacity-75 px-2 py-1 rounded">TANDON</span>
                </div>
            </div>

            <div class="mt-3">
                <span class="text-muted small d-block">Status Katup Solenoid:</span>
                <h4 class="fw-bold {{ $wc->command === 'FILL' ? 'text-success' : 'text-secondary' }} mb-0">
                    <i class="fas {{ $wc->command === 'FILL' ? 'fa-circle-play' : 'fa-circle-pause' }} me-1"></i>
                    {{ $wc->command === 'FILL' ? 'SOLENOID VALVE MENYALA (ON)' : 'SOLENOID VALVE MATI (OFF)' }}
                </h4>
            </div>
        </div>
    </div>

    <!-- Panel Kontrol Aktuator -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-sliders me-2 text-primary"></i>Operasi Aktuator Pengisian</h6>
                <p class="text-muted small">
                    Perintah manual akan dieksekusi secara instan oleh ESP32 controller melalui polling status / WebSocket.
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
                            <span class="text-muted small d-block mb-1">Waktu Pembaruan Terakhir</span>
                            <span class="fw-bold text-dark small d-block">{{ $wc->updated_at ? \Carbon\Carbon::parse($wc->updated_at)->format('d M Y, H:i:s') : 'Belum pernah update' }}</span>
                            <small class="text-muted" style="font-size: 0.72rem;">Tersinkronisasi dengan Database</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label small fw-bold text-secondary">Aksi Manual Katup (Override Solenoid):</label>
                    <div class="d-flex gap-3 flex-wrap">
                        <form action="{{ route('watercontrol.fill') }}" method="POST" onsubmit="return confirm('Mulai pengisian air sekarang? Pastikan pasokan air utama tersedia.')">
                            @csrf
                            <button type="submit" class="btn btn-emerald px-4 py-2 rounded-3 d-flex align-items-center gap-2">
                                <i class="fas fa-circle-play fs-5"></i>
                                <span>Buka Katup (Mulai Isi)</span>
                            </button>
                        </form>

                        <form action="{{ route('watercontrol.stop') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger px-4 py-2 rounded-3 d-flex align-items-center gap-2">
                                <i class="fas fa-circle-stop fs-5"></i>
                                <span>Tutup Katup (Stop Isi)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning border-0 rounded-3 small mt-4 mb-0 d-flex gap-2 align-items-start" style="background-color: #fffbeb; color: #92400e;">
                <i class="fas fa-shield-halved mt-1"></i>
                <div>
                    <strong>Protokol Keamanan Otomasi:</strong>
                    Pada mode <strong>AUTO</strong>, solenoid akan menyala jika float sensor mendeteksi level air rendah (&lt; batas bawah) dan otomatis mati saat menyentuh sensor atas untuk mencegah tumpahan.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
