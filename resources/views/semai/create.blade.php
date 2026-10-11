@extends('layouts.app')
@section('title', 'Tambah Semai')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-success text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-seedling fs-4"></i>
                    <div>
                        <h5 class="mb-0">🌿 Tambah Data Semai</h5>
                        <small class="opacity-75">Isi data batch — siklus & tanggal selesai terisi otomatis dari master data</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('semai.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-box-seedling text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Data Batch</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tanaman <span class="text-danger">*</span></label>
                        <input text name="id_tanaman" class="form-control" value="{{ old('id_tanaman') }}" >
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Meja Semai</label>
                        <select name="id_meja" class="form-select">
                            <option value="">-- Tanpa Meja --</option>
                            @foreach($mejas as $m)
                            <option value="{{ $m->id_meja }}" {{ old('id_meja') == $m->id_meja ? 'selected':'' }}>{{ $m->meja }} ({{ $m->jumlah_lubang }} lubang)</option>
                            @endforeach
                        </select>@error('id_meja')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Siklus <span class="badge bg-success-subtle text-success ms-1">✨ Otomatis</span></label>
                        <select name="id_siklus" class="form-select" id="pilih-siklus">
                            <option value="">-- Tanpa Siklus (opsional) --</option>
                            @foreach($siklus as $sk)
                            <option value="{{ $sk->id_siklus }}" data-tanaman="{{ $sk->id_tanaman }}" data-nama="{{ strtolower($sk->nama_siklus) }}" data-waktu="{{ $sk->waktu_siklus }}" {{ old('id_siklus') == $sk->id_siklus ? 'selected':'' }}>{{ $sk->nama_siklus }} ({{ $sk->waktu_siklus }} hari)</option>
                            @endforeach
                        </select>@error('id_siklus')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <small class="text-muted">Terisi otomatis dari master Durasi Siklus sesuai tanaman</small>
                        <small id="peringatan-siklus" class="text-warning d-none">⚠ Tanaman ini belum punya Durasi Siklus — tetap bisa disimpan (tgl selesai dihitung 7 hari). Buat di menu Master → Durasi Siklus.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Jumlah Benih <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah_benih" class="form-control @error('jumlah_benih') is-invalid @enderror" value="{{ old('jumlah_benih') }}" min="1" required>@error('jumlah_benih')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2 mt-2">
                        <i class="fas fa-calendar-days text-primary small"></i>
                        <span class="fw-bold small text-uppercase text-muted">Periode Penyemaian</span>
                        <hr class="flex-grow-1 ms-2 my-0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tgl Mulai Penyemaian <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_awal_semai" class="form-control @error('tgl_awal_semai') is-invalid @enderror" value="{{ old('tgl_awal_semai') }}" id="tgl-awal-semai" required>@error('tgl_awal_semai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tgl Selesai Semai <span class="badge bg-success-subtle text-success ms-1">✨ Otomatis</span></label>
                        <input type="date" name="tgl_akhir_semai" class="form-control @error('tgl_akhir_semai') is-invalid @enderror" value="{{ old('tgl_akhir_semai') }}" id="tgl-akhir-semai" required>@error('tgl_akhir_semai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">= tgl mulai + durasi siklus master (bisa diubah manual)</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional...">{{ old('keterangan') }}</textarea>
                    </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('semai.index') }}" class="btn btn-outline-secondary px-4"><i class="fas fa-arrow-left me-1"></i>Batal</a>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const selTanaman = document.querySelector('select[name="id_tanaman"]');
    const selSiklus  = document.getElementById('pilih-siklus');
    const tglAwal    = document.getElementById('tgl-awal-semai');
    const tglAkhir   = document.getElementById('tgl-akhir-semai');
    if (!selSiklus) return;

    function opsi(id, nama) {
        return [...selSiklus.querySelectorAll('option[data-tanaman]')].find(o =>
            (!id || o.dataset.tanaman === id) && (!nama || o.dataset.nama === nama));
    }
    function filterDanPilih() {
        const id = selTanaman ? selTanaman.value : null;
        selSiklus.querySelectorAll('option[data-tanaman]').forEach(o => {
            const cocok = !id || o.dataset.tanaman === id;
            o.hidden = !cocok;
            o.disabled = !cocok;
        });
        const dipilih = selSiklus.selectedOptions[0];
        if (dipilih && dipilih.disabled) selSiklus.value = '';
        if (id && !selSiklus.value) {                       // siklus masuk OTOMATIS
            const kandidat = opsi(id, 'semai') || opsi(id, null);
            if (kandidat) selSiklus.value = kandidat.value;
        }
        const info = document.getElementById('peringatan-siklus');
        if (info) {
            const adaKandidat = [...selSiklus.querySelectorAll('option[data-tanaman]')].some(o => !o.disabled);
            info.classList.toggle('d-none', !id || adaKandidat);
        }
        hitungSelesai();
    }
    function hitungSelesai() {
        if (!tglAwal || !tglAkhir || !tglAwal.value) return;
        const dipilih = selSiklus.selectedOptions[0];
        const hari = (dipilih && dipilih.dataset.waktu) ? parseInt(dipilih.dataset.waktu, 10) : 7;
        const t = new Date(tglAwal.value + 'T00:00:00');
        t.setDate(t.getDate() + hari);
        tglAkhir.value = t.toISOString().slice(0, 10);      // tgl selesai OTOMATIS dari master
    }
    if (selTanaman) selTanaman.addEventListener('change', filterDanPilih);
    selSiklus.addEventListener('change', hitungSelesai);
    if (tglAwal) tglAwal.addEventListener('change', hitungSelesai);
    filterDanPilih();
})();
</script>
@endpush
@endsection