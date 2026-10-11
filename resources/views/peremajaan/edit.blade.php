@extends('layouts.app')
@section('title', 'Edit Peremajaan')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-warning text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-pen-to-square fs-4"></i>
                    <div>
                        <h5 class="mb-0">✏️ Edit Peremajaan</h5>
                        <small class="opacity-75">Perbarui data — tanggal selesai terisi otomatis dari durasi siklus master</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('peremajaan.update',$peremajaan->id_peremajaan) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-link text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Asal Bibit & Meja</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Dari Semai <span class="text-danger">*</span></label>
                        <select name="id_semai" id="pilih-induk" class="form-select" required>
                            <option value="">-- Pilih Semai --</option>
                            @foreach($semais as $s)
                            <option value="{{ $s->id_semai }}" data-tanaman="{{ $s->id_tanaman }}" {{ old('id_semai', $peremajaan->id_semai) == $s->id_semai ? 'selected':'' }}>{{ trim(($s->tanaman?->nama_tanaman ?? 'Tanaman ?') . ' — ' . $s->jumlah_benih . ' benih, ' . $s->tgl_awal_semai) }}</option>
                            @endforeach
                        </select>@error('id_semai')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Meja Peremajaan</label>
                        <select name="id_meja" class="form-select">
                            <option value="">-- Tanpa Meja --</option>
                            @foreach($mejas as $m)
                            <option value="{{ $m->id_meja }}" {{ old('id_meja', $peremajaan->id_meja) == $m->id_meja ? 'selected':'' }}>{{ $m->meja }} ({{ $m->jumlah_lubang }} lubang)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-circle-check text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Hasil Bibit</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Benih Berhasil <span class="text-danger">*</span></label>
                        <input type="number" name="benih_berhasil" class="form-control @error('benih_berhasil') is-invalid @enderror" value="{{ old('benih_berhasil', $peremajaan->benih_berhasil) }}" required>@error('benih_berhasil')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Benih Gagal <span class="text-danger">*</span></label>
                        <input type="number" name="benih_gagal" class="form-control @error('benih_gagal') is-invalid @enderror" value="{{ old('benih_gagal', $peremajaan->benih_gagal) }}" required>@error('benih_gagal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-calendar-days text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Periode Peremajaan</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tgl Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_awal_peremajaan" class="form-control @error('tgl_awal_peremajaan') is-invalid @enderror" value="{{ old('tgl_awal_peremajaan', $peremajaan->tgl_awal_peremajaan) }}" id="tgl-awal" required>@error('tgl_awal_peremajaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tgl Selesai <span class="badge bg-success-subtle text-success ms-1">✨ Otomatis</span></label>
                        <input type="date" name="tgl_akhir_peremajaan" class="form-control @error('tgl_akhir_peremajaan') is-invalid @enderror" value="{{ old('tgl_akhir_peremajaan', $peremajaan->tgl_akhir_peremajaan) }}" id="tgl-akhir" required>@error('tgl_akhir_peremajaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">= tgl mulai + durasi siklus master (bisa diubah manual)</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional...">{{ old('keterangan', $peremajaan->keterangan) }}</textarea>
                    </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('peremajaan.index') }}" class="btn btn-outline-secondary px-4"><i class="fas fa-arrow-left me-1"></i>Batal</a>
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
    const TAHAP = 'peremajaan';
    const DEFAULT_HARI = 14;

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