@extends('layouts.app')
@section('title', 'Edit Pendewasaan')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-warning text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-pen-to-square fs-4"></i>
                    <div>
                        <h5 class="mb-0">✏️ Edit Pendewasaan</h5>
                        <small class="opacity-75">Perbarui data — tanggal selesai terisi otomatis dari durasi siklus master</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('pendewasaan.update',$pendewasaan->id_pendewasaan) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-link text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Asal & Meja</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Dari Peremajaan <span class="text-danger">*</span></label>
                        <select name="id_peremajaan" id="pilih-induk" class="form-select" required>
                            <option value="">-- Pilih Peremajaan --</option>
                            @foreach($peremajaans as $p)
                            <option value="{{ $p->id_peremajaan }}" data-tanaman="{{ $p->semai?->id_tanaman }}" {{ old('id_peremajaan', $pendewasaan->id_peremajaan) == $p->id_peremajaan ? 'selected':'' }}>{{ trim(($p->semai?->tanaman?->nama_tanaman ?? 'Tanaman ?') . ' — Meja ' . ($p->meja?->meja ?? '-') . ' — ' . $p->benih_berhasil . ' benih') }}</option>
                            @endforeach
                        </select>@error('id_peremajaan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Meja Pendewasaan</label>
                        <select name="id_meja" class="form-select">
                            <option value="">-- Tanpa Meja --</option>
                            @foreach($mejas as $m)
                            <option value="{{ $m->id_meja }}" {{ old('id_meja', $pendewasaan->id_meja) == $m->id_meja ? 'selected':'' }}>{{ $m->meja }} ({{ $m->jumlah_lubang }} lubang)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-circle-check text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Populasi Tanaman</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tanaman Berhasil <span class="text-danger">*</span></label>
                        <input type="number" name="tanaman_berhasil" class="form-control @error('tanaman_berhasil') is-invalid @enderror" value="{{ old('tanaman_berhasil', $pendewasaan->tanaman_berhasil) }}" required>@error('tanaman_berhasil')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tanaman Gagal <span class="text-danger">*</span></label>
                        <input type="number" name="tanaman_gagal" class="form-control @error('tanaman_gagal') is-invalid @enderror" value="{{ old('tanaman_gagal', $pendewasaan->tanaman_gagal) }}" required>@error('tanaman_gagal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-calendar-days text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Periode Pendewasaan</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tgl Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_awal_pendewasaan" class="form-control @error('tgl_awal_pendewasaan') is-invalid @enderror" value="{{ old('tgl_awal_pendewasaan', $pendewasaan->tgl_awal_pendewasaan) }}" id="tgl-awal" required>@error('tgl_awal_pendewasaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tgl Selesai <span class="badge bg-success-subtle text-success ms-1">✨ Otomatis</span></label>
                        <input type="date" name="tgl_akhir_pendewasaan" class="form-control @error('tgl_akhir_pendewasaan') is-invalid @enderror" value="{{ old('tgl_akhir_pendewasaan', $pendewasaan->tgl_akhir_pendewasaan) }}" id="tgl-akhir" required>@error('tgl_akhir_pendewasaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">= tgl mulai + durasi siklus master (bisa diubah manual)</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional...">{{ old('keterangan', $pendewasaan->keterangan) }}</textarea>
                    </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('pendewasaan.index') }}" class="btn btn-outline-secondary px-4"><i class="fas fa-arrow-left me-1"></i>Batal</a>
                        <button type="submit" class="btn btn-warning px-4"><i class="fas fa-save me-1"></i>Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const selInduk   = document.getElementById('pilih-induk');
    const tglAwal    = document.getElementById('tgl-awal');
    const tglAkhir   = document.getElementById('tgl-akhir');
    const DURASI = {};
    @foreach($siklus as $sk)
    DURASI['{{ $sk->id_tanaman }}|{{ strtolower($sk->nama_siklus) }}'] = {{ (int) $sk->waktu_siklus }};
    @endforeach
    const TAHAP = 'pendewasaan';
    const DEFAULT_HARI = 21;

    function hitungSelesai() {
        if (!tglAwal || !tglAkhir || !tglAwal.value) return;
        let id = selInduk ? (selInduk.selectedOptions[0] ? selInduk.selectedOptions[0].dataset.tanaman : '') : '';
        const hari = DURASI[(id || '') + '|' + TAHAP] || DEFAULT_HARI;
        const t = new Date(tglAwal.value + 'T00:00:00');
        t.setDate(t.getDate() + hari);
        tglAkhir.value = t.toISOString().slice(0, 10);
    }
    if (selInduk) selInduk.addEventListener('change', hitungSelesai);
    if (tglAwal) tglAwal.addEventListener('change', hitungSelesai);
    hitungSelesai();
})();
</script>
@endpush
@endsection