@extends('layouts.app')
@section('title', 'Notifikasi WhatsApp Gateway')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fab fa-whatsapp me-2 text-success"></i>Pusat Notifikasi & Gateway WhatsApp</h4>
        <p class="text-muted small mb-0">Integrasi API Fonnte untuk peringatan dini sensor, laporan berkala, dan pengingat siklus H-1</p>
    </div>
    <a href="{{ route('penerima_notif.index') }}" class="btn btn-emerald btn-sm px-3 py-2">
        <i class="fas fa-address-book me-1"></i> Kelola Penerima ({{ \App\Models\PenerimaNotif::count() }})
    </a>
</div>

<!-- INFO GATEWAY STATUS -->
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff;">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-white bg-opacity-25 text-white mb-2">Fonnte WhatsApp API</span>
                <h5 class="fw-bold mb-1">Gateway Otomasi Terhubung</h5>
                <p class="text-white text-opacity-90 small mb-0">
                    Sistem secara otomatis mengirimkan peringatan jika parameter sensor (pH, Suhu, TDS, Air Tandon) keluar dari ambang batas aman dengan proteksi cooldown anti-spam.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="badge bg-white text-success px-3 py-2 fw-bold fs-6">
                    <i class="fas fa-circle-check me-1"></i> STATUS: AKTIF
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Test Kirim Pesan -->
    <div class="col-md-4">
        <div class="card card-elevated border-0 shadow-sm h-100 p-4 text-center">
            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background: #ecfdf5; color: #059669;">
                <i class="fab fa-whatsapp fs-2"></i>
            </div>
            <h6 class="fw-bold text-dark">Uji Koneksi WhatsApp</h6>
            <p class="text-muted small mb-4">Kirim pesan uji coba ke seluruh nomor staf penerima yang terdaftar.</p>
            <button id="btn-test-wa" class="btn btn-emerald w-100 mt-auto py-2" onclick="sendTestWA()">
                <i class="fas fa-paper-plane me-2"></i>Kirim Pesan Uji Coba
            </button>
        </div>
    </div>

    <!-- Kirim Smart Report 6 Jam -->
    <div class="col-md-4">
        <div class="card card-elevated border-0 shadow-sm h-100 p-4 text-center">
            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background: #eff6ff; color: #2563eb;">
                <i class="fas fa-file-waveform fs-2"></i>
            </div>
            <h6 class="fw-bold text-dark">Kirim Smart Report (6 Jam)</h6>
            <p class="text-muted small mb-4">Kirimkan ringkasan rata-rata sensor 6 jam terakhir ke grup / penerima sekarang.</p>
            <button id="btn-send-report" class="btn btn-primary w-100 mt-auto py-2" onclick="sendReportWA()">
                <i class="fas fa-chart-simple me-2"></i>Kirim Laporan Manual
            </button>
        </div>
    </div>

    <!-- Threshold Sensor -->
    <div class="col-md-4">
        <div class="card card-elevated border-0 shadow-sm h-100 p-4 text-center">
            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background: #fffbeb; color: #d97706;">
                <i class="fas fa-sliders fs-2"></i>
            </div>
            <h6 class="fw-bold text-dark">Ambang Batas (Threshold)</h6>
            <p class="text-muted small mb-4">Sistem menggunakan batas ini untuk mengirim pesan darurat jika air pupuk kurang atau terlalu pekat.</p>
            <button class="btn btn-warning text-dark fw-semibold w-100 mt-auto py-2" data-bs-toggle="modal" data-bs-target="#modalThresholds">
                <i class="fas fa-eye me-2"></i>Tinjau Batas Sensor
            </button>
        </div>
    </div>
</div>

<!-- FEEDBACK ALERT AREA -->
<div id="result-box" class="d-none alert border-0 shadow-sm rounded-3 p-3 mb-4"></div>

<!-- MODAL THRESHOLDS -->
<div class="modal fade" id="modalThresholds" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold"><i class="fas fa-sliders me-2 text-warning"></i>Batas Aman Kondisi Tanaman</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th class="ps-4">Sensor</th><th>Min</th><th>Max</th><th class="pe-4">Satuan</th></tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\SensorThreshold::all() as $st)
                        <tr>
                            <td class="ps-4 fw-bold">{{ ucfirst($st->sensor) }}</td>
                            <td><span class="badge badge-soft-primary">{{ $st->min }}</span></td>
                            <td><span class="badge badge-soft-danger">{{ $st->max }}</span></td>
                            <td class="pe-4 text-muted small">
                                @if($st->sensor === 'waterTemp' || $st->sensor === 'airTemp') °C
                                @elseif($st->sensor === 'tds') ppm
                                @elseif($st->sensor === 'humidity') %
                                @else pH
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
async function sendTestWA() {
    const btn = document.getElementById('btn-test-wa');
    const box = document.getElementById('result-box');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengirim ke Fonnte...';

    try {
        const res = await fetch('{{ route("notifikasi-wa.test") }}');
        const data = await res.json();
        box.classList.remove('d-none', 'alert-danger');
        box.classList.add('alert-success');
        box.innerHTML = '<i class="fas fa-circle-check me-2"></i>' + (data.status ? 'Berhasil dikirim ke ' + data.kirim_ke + ' penerima!' : 'Gagal mengirim pesan.');
    } catch (e) {
        box.classList.remove('d-none', 'alert-success');
        box.classList.add('alert-danger');
        box.innerHTML = '<i class="fas fa-circle-xmark me-2"></i>Terjadi kesalahan saat memanggil API WhatsApp.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Kirim Pesan Uji Coba';
    }
}

async function sendReportWA() {
    const btn = document.getElementById('btn-send-report');
    const box = document.getElementById('result-box');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses Laporan...';

    try {
        const res = await fetch('{{ route("notifikasi-wa.report") }}');
        const data = await res.json();
        box.classList.remove('d-none', 'alert-danger');
        box.classList.add('alert-success');
        box.innerHTML = '<strong><i class="fas fa-circle-check me-2"></i>Laporan Berhasil Terkirim:</strong><pre class="mt-2 mb-0 small text-dark p-2 bg-light rounded">' + data.message + '</pre>';
    } catch (e) {
        box.classList.remove('d-none', 'alert-success');
        box.classList.add('alert-danger');
        box.innerHTML = '<i class="fas fa-circle-xmark me-2"></i>Gagal memproses pengiriman laporan.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-chart-simple me-2"></i>Kirim Laporan Manual';
    }
}
</script>
@endsection
