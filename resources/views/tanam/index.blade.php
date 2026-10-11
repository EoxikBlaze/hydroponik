@extends('layouts.app')
@section('title', 'Tanam Saya')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-seedling me-2 text-success"></i>Tanam Saya</h4>
        <p class="text-muted small mb-0">Mulai tanam sekali klik — semai, peremajaan, pendewasaan, panen berjalan otomatis sesuai durasi master data.</p>
    </div>
    <button class="btn btn-success px-4" data-bs-toggle="modal" data-bs-target="#modalMulai">
        <i class="fas fa-plus me-1"></i>Mulai Tanam
    </button>
</div>

<!-- Alert success TIDAK dirender di sini — layout/layouts/app.blade.php sudah
     punya blok session('success'). Dulu dobel 2x (view + layout). -->

<!-- FILTER: CARI + TAMPIL PER BULAN -->
<form method="GET" action="{{ route('tanam.index') }}" class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-0">Cari</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" value="{{ $qCari }}" class="form-control"
                           placeholder="Nama tanaman, No. batch (#), atau keterangan...">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-0">Tampilkan per bulan (tgl semai)</label>
                <select name="bulan" class="form-select form-select-sm">
                    <option value="">— Semua bulan —</option>
                    @foreach($daftarBulan as $b)
                        <option value="{{ $b }}" {{ $bulanAktif === $b ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $b)->locale('id')->isoFormat('MMMM Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <div class="d-grid gap-1">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Terapkan</button>
                    <a href="{{ route('tanam.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </div>
        </div>
        @if($qCari !== '' || $bulanAktif !== '')
            <div class="mt-2 small">
                <span class="badge badge-soft-info">
                    {{ $rantai->count() }} batch cocok
                    @if($qCari !== '') · cari "<strong>{{ $qCari }}</strong>" @endif
                    @if($bulanAktif !== '') · {{ \Carbon\Carbon::createFromFormat('Y-m', $bulanAktif)->locale('id')->isoFormat('MMMM Y') }} @endif
                </span>
            </div>
        @endif
    </div>
</form>

<!-- KARTU TAHAPAN -->
@forelse($perBulan as $kodeBulan => $grupBulan)
    <div class="d-flex align-items-center gap-2 mb-2 mt-1">
        <span class="badge px-3 py-2" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;">
            <i class="far fa-calendar me-1"></i>
            {{ $kodeBulan === 'lainnya' ? 'Tanpa Tanggal' : \Carbon\Carbon::createFromFormat('Y-m', $kodeBulan)->locale('id')->isoFormat('MMMM Y') }}
        </span>
        <span class="small text-muted">{{ $grupBulan->count() }} batch</span>
        <hr class="flex-grow-1 mb-0">
    </div>

    <div class="row g-3 mb-4">
    @foreach($grupBulan as $r)
        @php
            $s  = $r['semai'];
            $lbl = $s->tanaman?->nama_tanaman ?? 'Tanaman';
            $meja = $s->tanaman?->meja?->meja ?? '-';
        @endphp
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">🌿 {{ $lbl }} <small class="text-muted fw-normal">— Batch #{{ $s->id_semai }}</small></h5>
                            <small class="text-muted">{{ $s->jumlah_benih }} benih · mulai {{ $s->tgl_awal_semai }} · Semai di <strong>{{ $s->meja?->meja ?? '-' }}</strong></small>
                        </div>
                        @if($r['tombol'] === [])
                            {{-- Tidak ada baris yang tersisa ditandai: siklus tuntas. --}}
                            <span class="badge badge-soft-success px-3 py-2">🌾 Siklus Tuntas</span>
                        @else
                            @if(auth()->user()->level === 'admin')
                                <form action="{{ route('tanam.hapus', $s->id_semai) }}" method="POST" class="d-inline me-2"
                                      onsubmit="return confirm('Hapus Batch #{{ $s->id_semai }} beserta SEMUA tahapannya? Data tidak bisa dikembalikan.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-3">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </button>
                                </form>
                            @endif
                            {{-- SATU TOMBOL PER BARIS yang belum lanjut — masing-masing
                                 membawa batasnya sendiri (jumlah_masuk baris itu), jadi
                                 angka pada form = angka yang diterima server. Setelah split,
                                 satu kartu bisa menampilkan beberapa tombol sekaligus. --}}
                            @foreach($r['tombol'] as $tb)
                                <button class="btn btn-sm btn-primary px-3 me-1 mb-1"
                                    data-bs-toggle="modal" data-bs-target="#modalSelesai"
                                    data-tipe="{{ $tb['tipe'] }}"
                                    data-id="{{ $tb['id'] }}"
                                    data-judul="{{ $tb['judul'] }}"
                                    data-batas="{{ $tb['batas'] }}"
                                    data-meja="{{ $tb['meja'] ?? '' }}">
                                    <i class="fas fa-check me-1"></i>{{ $tb['judul'] }}
                                    @if($tb['meja'])
                                        <small class="opacity-75">· {{ $tb['meja'] }}</small>
                                    @endif
                                </button>
                            @endforeach
                        @endif
                    </div>

                    <!-- PROGRESS 4 TAHAP -->
                    @php
                        $steps = [
                            ['Semai',      $s->tgl_awal_semai,  $s->tgl_akhir_semai,  $s->benih_berhasil,  $s->benih_gagal,  $s->meja?->meja,  route('semai.edit', $s->id_semai)],
                            ['Peremajaan', $r['per']?->tgl_awal_peremajaan,  $r['per']?->tgl_akhir_peremajaan,  $r['per']?->benih_berhasil,  $r['per']?->benih_gagal,  $r['per']?->meja?->meja,  $r['per'] ? route('peremajaan.edit', $r['per']->id_peremajaan) : null],
                            ['Pendewasaan',$r['pen']?->tgl_awal_pendewasaan, $r['pen']?->tgl_akhir_pendewasaan, $r['pen']?->tanaman_berhasil, $r['pen']?->tanaman_gagal,  $r['pen']?->meja?->meja,  $r['pen'] ? route('pendewasaan.edit', $r['pen']->id_pendewasaan) : null],
                            ['Panen',      $r['pan']?->tgl_panen, $r['pan']?->tgl_panen, $r['pan']?->panen_berhasil, $r['pan']?->panen_gagal,  null,  $r['pan'] ? route('panen.edit', $r['pan']->id_panen) : null],
                        ];
                    @endphp
                    <div class="row g-2">
                        @foreach($steps as $i => $st)
                            @php $no = $i + 1; @endphp
                            <div class="col-md-3 col-6">
                                <div class="rounded-3 p-2 h-100 {{ $no < $r['tahap'] ? 'bg-success bg-opacity-10' : ($no === $r['tahap'] ? 'bg-primary bg-opacity-10' : 'bg-light') }}">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge rounded-pill {{ $no < $r['tahap'] ? 'bg-success' : ($no === $r['tahap'] ? 'bg-primary' : 'bg-secondary bg-opacity-50') }}">{{ $no }}</span>
                                        <small class="fw-bold">{{ $st[0] }}</small>
                                        @if($st[5])<small class="text-muted">· {{ $st[5] }}</small>@endif
                                        @if(auth()->user()->level === 'admin' && ($st[6] ?? null))<a href="{{ $st[6] }}" class="ms-auto small" title="Edit detail"><i class="fas fa-pen-to-square"></i></a>@endif
                                        @if($no < $r['tahap'])
                                            <i class="fas fa-circle-check text-success ms-auto"></i>
                                        @elseif($no === $r['tahap'])
                                            <i class="fas fa-spinner text-primary ms-auto"></i>
                                        @endif
                                    </div>
                                    @if($st[1])
                                        <small class="text-muted d-block">{{ $st[1] }} → {{ $st[2] }}</small>
                                        <small class="text-muted">✅ {{ $st[3] ?? 0 }} · ❌ {{ $st[4] ?? 0 }}</small>
                                    @else
                                        <small class="text-muted">menunggu</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    </div>
@empty
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm"><div class="card-body text-center py-5 text-muted">
                <i class="fas fa-seedling fa-2x mb-3 opacity-50"></i>
                <p class="mb-1 fw-semibold">Belum ada tanaman</p>
                <p class="small mb-0">
                    @if($qCari !== '' || $bulanAktif !== '')
                        Tidak ada batch yang cocok dengan filter saat ini. Coba ubah kata kunci atau pilih "Semua bulan".
                    @else
                        Klik <strong>Mulai Tanam</strong> untuk memulai — semuanya otomatis!
                    @endif
                </p>
            </div></div>
        </div>
    </div>
@endforelse

<!-- MODAL: MULAI TANAM -->
<div class="modal fade" id="modalMulai" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('tanam.mulai') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title mb-0"><i class="fas fa-plus me-1"></i>Mulai Tanam</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Tanaman</label>
                        <select name="id_tanaman" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach($tanamans as $t)
                                <option value="{{ $t->id_tanaman }}" {{ old('id_tanaman') == $t->id_tanaman ? 'selected':'' }}>{{ $t->nama_tanaman }}</option>
                            @endforeach
                        </select>
                        @error('id_tanaman')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Meja Semai</label>
                        <select name="id_meja" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach($mejas as $m)
                                <option value="{{ $m->id_meja }}" {{ old('id_meja') == $m->id_meja ? 'selected':'' }}>{{ $m->meja }} ({{ $m->jumlah_lubang }} lubang)</option>
                            @endforeach
                        </select>
                        @error('id_meja')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Jumlah Benih</label>
                        <input type="number" name="jumlah_benih" class="form-control" value="{{ old('jumlah_benih', 10) }}" min="1" required>
                        <small class="text-muted">Tidak boleh melebihi lubang meja semai</small>
                        @error('jumlah_benih')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Tgl Mulai</label>
                        <input type="date" name="tgl_mulai" class="form-control" value="{{ old('tgl_mulai', date('Y-m-d')) }}" required>
                        @error('tgl_mulai')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="alert alert-light border small mb-0">
                    <i class="fas fa-wand-magic-sparkles me-1 text-success"></i>
                    Tahap <strong>Semai → Peremajaan → Pendewasaan</strong> dibuat otomatis mengikuti <strong>Durasi Siklus</strong> master data. Anda cukup menandai tiap tahap selesai.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-play me-1"></i>Mulai!</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: TANDAI SELESAI / CATAT PANEN -->
<div class="modal fade" id="modalSelesai" tabindex="-1">
    <div class="modal-dialog">
        <form id="formSelesai" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title mb-0" id="judulSelesai">Tandai Selesai</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Berhasil</label>
                        <input type="number" name="berhasil" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Gagal</label>
                        <input type="number" name="gagal" class="form-control" value="0" min="0" required>
                    </div>
                </div>
                <div class="row" id="barisPanen" style="display:none;">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">🌾 Panen Berhasil</label>
                        <input type="number" name="panen_berhasil" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-bold">Panen Gagal</label>
                        <input type="number" name="panen_gagal" class="form-control" value="0" min="0">
                    </div>
                </div>
                @php
                    // Error yang menempel di field tujuan[] agar tidak hilang
                    // (key-nya bernomor: tujuan.0.id_meja, dst).
                    $errTujuan = [];
                    foreach ($errors->messages() as $k => $msgs) {
                        if ($k === 'tujuan' || str_starts_with($k, 'tujuan.')) {
                            $errTujuan = array_merge($errTujuan, $msgs);
                        }
                    }
                @endphp
                @if(count($errTujuan))
                    <div class="alert alert-danger small mb-3" id="errTujuan">
                        <div class="fw-bold mb-1">Meja tujuan belum lengkap:</div>
                        @foreach($errTujuan as $m)<div>{{ $m }}</div>@endforeach
                    </div>
                @endif
                <div class="mb-3" id="barisMeja" style="display:none;">
                    <label class="form-label fw-bold">Meja Fase Berikutnya</label>
                    {{--
                        INDEKS EKSPLISIT (tujuan[0][...]) — WAJIB.
                        nama `tujuan[][x]` membuat PHP memecah tiap field jadi
                        elemen array TERPISAH: 1 baris -> 2 entri parsial yang
                        saling melengkapi, sehingga validasi selalu gagal
                        ("tujuan.1.id_meja wajib" padahal terisi).
                        Indeks diatur ulang oleh JS renumberTujuan() tiap kali
                        baris ditambah/dihapus.
                    --}}
                    <div id="daftarTujuan" class="d-flex flex-column gap-2">
                        <div class="row g-2 align-items-center tujuan-baris">
                            <div class="col-7">
                                <select name="tujuan[0][id_meja]" class="form-select form-select-sm">
                                    <option value="">-- Pilih Meja --</option>
                                    @foreach($mejas as $m)
                                        <option value="{{ $m->id_meja }}" data-lubang="{{ $m->jumlah_lubang }}">
                                            {{ $m->meja }} ({{ $m->jumlah_lubang }} lubang)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <input type="number" name="tujuan[0][jumlah]" class="form-control form-control-sm" min="1" placeholder="Jumlah">
                            </div>
                            <div class="col-1 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-tujuan" title="Hapus baris">&times;</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnTambahTujuan">
                        <i class="fas fa-plus me-1"></i>Tambah Meja
                    </button>
                    <div id="ringkasTujuan" class="small mt-1 text-muted"></div>
                    <small class="text-muted d-block">Boleh lebih dari satu meja — hasil dibelah (split). Total alokasi harus sama dengan jumlah <strong>berhasil</strong>, dan tiap meja harus muat dengan jatahnya sendiri.</small>
                    @error('id_meja')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tanggal Selesai</label>
                    <input type="date" name="tgl_selesai" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="alert alert-light border small mb-0" id="infoBatas"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>Simpan & Lanjut Otomatis</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalSelesai');
    const inB  = document.querySelector('#formSelesai [name=berhasil]');
    const inG  = document.querySelector('#formSelesai [name=gagal]');
    const inPB = document.querySelector('#formSelesai [name=panen_berhasil]');
    const inPG = document.querySelector('#formSelesai [name=panen_gagal]');
    let batas = 0, tipe = '';

    const angka = v => { const n = parseInt(v, 10); return isNaN(n) ? 0 : n; };
    const clamp = (v, m) => Math.min(Math.max(v, 0), m);

    // gagal = benih - berhasil (dikunci berimpit, berlaku dua arah)
    function sinkron(src) {
        let bsr = clamp(angka(inB.value), batas);
        let gag = clamp(angka(inG.value), batas);
        if (src === 'g') bsr = batas - gag; else gag = batas - bsr;
        inB.value = bsr; inG.value = gag;
        if (tipe === 'pendewasaan') {
            let pb = clamp(angka(inPB.value), bsr);
            let pg = clamp(angka(inPG.value), bsr);
            if (src === 'pg') pb = bsr - pg; else pg = bsr - pb;
            inPB.value = pb; inPG.value = pg;
        }
    }

    modal.addEventListener('show.bs.modal', function (e) {
        const b = e.relatedTarget;
        tipe = b.dataset.tipe; batas = angka(b.dataset.batas);
        const id = b.dataset.id, judul = b.dataset.judul;
        document.getElementById('formSelesai').action = '/tanam/' + tipe + '/' + id + '/selesai';
        document.getElementById('judulSelesai').textContent = judul;
        document.getElementById('barisPanen').style.display = (tipe === 'pendewasaan') ? 'flex' : 'none';
        document.getElementById('barisMeja').style.display = (tipe === 'pendewasaan') ? 'none' : 'block';
        // Error tujuan bersifat lintas-permintaan (tersimpan di session). Bila
        // modal dibuka untuk tahap Panen, error itu TIDAK relevan -> sembunyikan
        // supaya tidak bikin bingung (terlihat di modal "Catat Hasil Panen").
        const errT = document.getElementById('errTujuan');
        if (errT) errT.style.display = (tipe === 'pendewasaan') ? 'none' : 'block';
        // Wajibkan SEMUA select meja tujuan hanya saat bukan tahap panen.
        document.querySelectorAll('#barisMeja select').forEach(function (sel) {
            sel.required = (tipe !== 'pendewasaan');
        });
        const batasTxt = (tipe === 'pendewasaan')
            ? 'Total tanaman tidak boleh melebihi <strong>' + batas + '</strong> (hasil peremajaan). Tahap berikut: <strong>Panen</strong>.'
            : 'Total berhasil + gagal tidak boleh melebihi <strong>' + batas + '</strong>. Boleh split ke beberapa meja — total alokasi wajib = <strong>berhasil</strong>.';
        document.getElementById('infoBatas').innerHTML = '<i class=\"fas fa-circle-info me-1\"></i>' + batasTxt;

        // AUTO-ISI: berhasil = seluruh benih sebelumnya, gagal = 0
        inB.value = batas; inG.value = 0;
        inPB.value = batas; inPG.value = 0;
        sinkron('b');

        // Selalu mulai dari SATU baris tujuan; jatahnya = jumlah berhasil.
        if (tipe !== 'pendewasaan') resetTujuan();
    });

    /* ---- SPLIT MEJA TUJUAN ---- */
    const daftarT = document.getElementById('daftarTujuan');
    const btnTambahT = document.getElementById('btnTambahTujuan');
    const ringkasT = document.getElementById('ringkasTujuan');
    const tplBaris = daftarT ? daftarT.querySelector('.tujuan-baris').cloneNode(true) : null;
    const lubangDari = sel => {
        const o = sel.options[sel.selectedIndex];
        const m = /\((\d+) lubang\)/.exec(sel.options[sel.selectedIndex]?.text || '');
        return m ? parseInt(m[1], 10) : 0;
    };

    function resetTujuan() {
        if (!daftarT) return;
        daftarT.innerHTML = '';
        const baris = tplBaris.cloneNode(true);
        daftarT.appendChild(baris);
        baris.querySelector('input').value = angka(inB.value);
        pilihMeja(baris);
        renumberTujuan();
        hitungTujuan();
    }

    function tambahTujuan() {
        if (!daftarT) return;
        const baris = tplBaris.cloneNode(true);
        daftarT.appendChild(baris);
        pilihMeja(baris);
        renumberTujuan();
        hitungTujuan();
        baris.querySelector('select').focus();
    }

    /**
     * Pilihkan meja untuk baris tujuan.
     *
     * Urutan prioritas: (1) yang belum dipakai baris lain DAN cukup lubang
     * untuk jumlah yang diminta; (2) yang belum dipakai apa pun kondisinya —
     * supaya kegagalan tetap menampilkan pesan jumlah yang jelas, bukan
     * "tujuan[0].id_meja field is required" padahal pengguna merasa form sudah
     * terisi lengkap.
     *
     * @param {HTMLElement} baris
     */
    function pilihMeja(baris) {
        const sel = baris.querySelector('select');
        if (!sel || sel.value) return;

        const butuh = angka(baris.querySelector('input')?.value);
        const terpakai = new Set(
            Array.from(daftarT.querySelectorAll('.tujuan-baris'))
                .map(function (b) { return b.querySelector('select').value; })
                .filter(Boolean)
        );
        const kandidat = Array.from(sel.options).filter(function (o) {
            return o.value !== '' && !terpakai.has(o.value);
        });
        const muat = kandidat.find(function (o) {
            const lubang = parseInt(o.dataset.lubang || '0', 10);
            return butuh > 0 && lubang >= butuh;
        });

        const pilihan = muat || kandidat[0];
        if (pilihan) sel.value = pilihan.value;
    }

    /**
     * WAJIB: beri indeks eksplisit tujuan[0], tujuan[1], ...
     * Nama `tujuan[][x]` membuat PHP memecah tiap field jadi entri array
     * terpisah sehingga validasi selalu gagal. Indeks dihitung ulang dari
     * posisi baris tiap kali daftar berubah.
     */
    function renumberTujuan() {
        if (!daftarT) return;
        daftarT.querySelectorAll('.tujuan-baris').forEach(function (baris, i) {
            const sel = baris.querySelector('select');
            const inp = baris.querySelector('input');
            if (sel) sel.name = 'tujuan[' + i + '][id_meja]';
            if (inp) inp.name = 'tujuan[' + i + '][jumlah]';
        });
    }

    // Ringkasan realtime + penanda meja yang kelebihan jatah.
    function hitungTujuan() {
        if (!daftarT) return;
        let total = 0, kelebihan = 0;
        daftarT.querySelectorAll('.tujuan-baris').forEach(function (baris) {
            const sel = baris.querySelector('select');
            const inp = baris.querySelector('input');
            const jml = angka(inp.value);
            const lub = lubangDari(sel);
            total += jml;
            const lebih = (sel.value !== '' && lub > 0 && jml > lub);
            inp.classList.toggle('is-invalid', lebih);
            if (lebih) kelebihan++;
        });
        const perlu = angka(inB.value);
        const sisa = perlu - total;
        if (!ringkasT) return;
        if (sisa === 0 && kelebihan === 0) {
            ringkasT.className = 'small mt-1 text-success';
            ringkasT.innerHTML = '<i class="fas fa-check-circle me-1"></i>Total alokasi <strong>' + total + '</strong> = berhasil. Siap disimpan.';
        } else if (kelebihan > 0) {
            ringkasT.className = 'small mt-1 text-danger';
            ringkasT.innerHTML = '<i class="fas fa-triangle-exclamation me-1"></i>Ada meja yang kelebihan jatah (melebihi jumlah lubangnya).';
        } else {
            ringkasT.className = 'small mt-1 text-warning';
            ringkasT.innerHTML = '<i class="fas fa-circle-info me-1"></i>Alokasi <strong>' + total + '</strong> / berhasil <strong>' + perlu + '</strong>'
                + (sisa > 0 ? ' — masih kurang ' + sisa : ' — kelebihan ' + (-sisa));
        }
    }

    if (btnTambahT) btnTambahT.addEventListener('click', tambahTujuan);
    if (daftarT) {
        daftarT.addEventListener('click', function (e) {
            const tombol = e.target.closest('.btn-hapus-tujuan');
            if (!tombol) return;
            // Baris pertama tidak boleh dihapus seluruhnya (minimal satu tujuan).
            if (daftarT.querySelectorAll('.tujuan-baris').length <= 1) return;
            tombol.closest('.tujuan-baris').remove();
            renumberTujuan();   // indeks harus dirapikan setelah ada yang dihapus
            hitungTujuan();
        });
        daftarT.addEventListener('input', hitungTujuan);
        daftarT.addEventListener('change', hitungTujuan);
    }

    // Auto-hitung realtime saat angka diubah
    if (inB)  inB.addEventListener('input', () => { sinkron('b'); hitungTujuan(); });
    if (inG)  inG.addEventListener('input', () => { sinkron('g'); hitungTujuan(); });
    if (inPB) inPB.addEventListener('input', () => sinkron('pb'));
    if (inPG) inPG.addEventListener('input', () => sinkron('pg'));

    // Anti dobel-klik: tombol Simpan dinonaktifkan saat submit agar tidak ada
    // POST kedua yang menunggu/menyusul sebelum halaman berpindah.
    document.getElementById('formSelesai').addEventListener('submit', function () {
        const btn = this.querySelector('button[type=submit]');
        if (btn) { btn.disabled = true; }
    });
});
</script>
@endpush
@endsection
