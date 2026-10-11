@extends('layouts.app')
@section('title', 'Data Tahap (Detail)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-layer-group me-2 text-success"></i>Data Tahap (Detail)</h4>
        <p class="text-muted small mb-0">Seluruh tahap siklus dalam satu tabel — Semai, Peremajaan, Pendewasaan, sampai Panen.</p>
    </div>
    <div class="d-flex gap-2">
        @if($tahapAktif === 'semai')
            <a href="{{ route('semai.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
                <i class="fas fa-plus me-1"></i> Mulai Semai Baru
            </a>
        @elseif($tahapAktif === 'peremajaan')
            <a href="{{ route('peremajaan.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
                <i class="fas fa-plus me-1"></i> Tambah Peremajaan
            </a>
        @elseif($tahapAktif === 'pendewasaan')
            <a href="{{ route('pendewasaan.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
                <i class="fas fa-plus me-1"></i> Tambah Pendewasaan
            </a>
        @else
            <a href="{{ route('panen.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
                <i class="fas fa-plus me-1"></i> Catat Panen Baru
            </a>
        @endif
    </div>
</div>

{{-- TAB TAHAP --}}
<ul class="nav nav-pills mb-3 flex-nowrap overflow-auto" style="gap:.35rem">
    @foreach($daftarTahap as $t)
        <li class="nav-item">
            <a class="nav-link py-2 px-3 rounded-pill text-nowrap {{ $tahapAktif === $t ? 'active' : '' }}"
               href="{{ route('semai.index', array_merge(request()->query(), ['tahap' => $t])) }}">
                <i class="fas {{ ['semai'=>'fa-seedling','peremajaan'=>'fa-water','pendewasaan'=>'fa-leaf','panen'=>'fa-wheat-awn'][$t] }} me-1"></i>
                {{ $labelTahap[$t] }}
            </a>
        </li>
    @endforeach
</ul>

{{-- FILTER --}}
<form method="GET" class="card border-0 shadow-sm mb-3">
    <input type="hidden" name="tahap" value="{{ $tahapAktif }}">
    <input type="hidden" name="per_page" value="{{ $perPageSize }}">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari nama tanaman / keterangan...">
            </div>
            @if($tahapAktif === 'semai')
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach(['proses', 'selesai', 'gagal'] as $st)
                        <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <label class="form-label small text-muted mb-0">Tanggal dari</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-0">sampai</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('semai.index', ['tahap' => $tahapAktif]) }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </div>
    </div>
</form>

{{-- TABEL --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        {{-- baris-per-halaman --}}
        <div class="d-flex justify-content-between align-items-center px-4 pt-3 pb-1 flex-wrap gap-2">
            <small class="text-muted">
                Menampilkan <strong>{{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }}</strong>
                dari <strong>{{ $rows->total() }}</strong> data {{ $labelTahap[$tahapAktif] }}
            </small>
            <form method="GET" class="d-flex align-items-center gap-2">
                <input type="hidden" name="tahap" value="{{ $tahapAktif }}">
                @foreach(request()->query() as $k => $v)
                    @if($k !== 'per_page' && $k !== 'tahap' && $k !== 'page')
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <label class="small text-muted mb-0">Baris/hal</label>
                <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ $perPageSize === $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:50px;">No</th>
                        <th>Tanaman</th>
                        <th>Meja</th>
                        @if($tahapAktif === 'panen')
                            <th>Tgl Panen</th>
                            <th>Berhasil</th>
                            <th>Gagal</th>
                        @else
                            <th>Periode</th>
                            <th>Berhasil</th>
                            <th>Gagal</th>
                        @endif
                        <th>Keterangan</th>
                        <th class="text-end pe-4" style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                    @php
                        // Seragamkan struktur lintas tahap.
                        // Semai = 1 baris per batch (id_semai sudah unik).
                        // Tahap lain DIGABUNG per batch: kolom `batch` berisi
                        // id_semai, dan sub-barisnya diambil dari $anak.
                        if ($tahapAktif === 'semai') {
                            $batch = $r->id_semai;
                            $nama = $r->tanaman?->nama_tanaman ?? '-';
                            $tgl1 = $r->tgl_awal_semai; $tgl2 = $r->tgl_akhir_semai;
                            $berhasil = $r->benih_berhasil; $gagal = $r->benih_gagal;
                            $ket = ($r->status ? ucfirst($r->status).' · ' : '').($r->keterangan ?? '');
                            $sub = collect([$r]);
                            $routeTahap = 'semai';
                            $konfirmasi = 'Hapus data semai ini?';
                        } else {
                            $batch = $r->batch;
                            $nama = $r->nama_tanaman ?? '-';
                            $tgl1 = $r->tgl1; $tgl2 = $r->tgl2;
                            $berhasil = (int) $r->berhasil; $gagal = (int) $r->gagal;
                            $ket = $r->keterangan ?? '';
                            $sub = $anak->get($batch, collect());
                            $routeTahap = $tahapAktif;
                            $konfirmasi = 'Hapus data '.$labelTahap[$tahapAktif].' ini?';
                        }
                        $jmlMeja = $sub->count();
                    @endphp
                    <tr>
                        <td class="ps-4 fw-bold text-muted">{{ $rows->firstItem() + $loop->index }}</td>
                        <td>
                            <span class="fw-bold text-dark">{{ $nama }}</span>
                            <div class="small text-muted">Batch <strong>#{{ $batch }}</strong>
                                @if($jmlMeja > 1)<span class="badge badge-soft-warning ms-1">{{ $jmlMeja }} meja</span>@endif
                            </div>
                        </td>
                        <td>
                            {{-- Satu badge per meja tujuan; split tetap TETAP SATU batch --}}
                            @foreach($sub as $s)
                                <span class="badge badge-soft-info">{{ $tahapAktif === 'semai'
                                    ? ($s->meja?->meja ?? '-')
                                    : ($s->meja?->meja ?? '-') }}</span>
                            @endforeach
                        </td>
                        @if($tahapAktif === 'panen')
                            <td><i class="far fa-calendar-check me-1 text-success"></i>{{ \Carbon\Carbon::parse($tgl1)->format('d M Y') }}</td>
                        @else
                            <td class="small text-dark">{{ \Carbon\Carbon::parse($tgl1)->format('d M Y') }} → {{ \Carbon\Carbon::parse($tgl2)->format('d M Y') }}</td>
                        @endif
                        <td><span class="badge badge-soft-success px-2 py-1">{{ $berhasil }}</span></td>
                        <td><span class="badge badge-soft-danger px-2 py-1">{{ $gagal }}</span></td>
                        <td><small class="text-muted">{{ $ket !== '' ? \Illuminate\Support\Str::limit($ket, 40) : '—' }}</small></td>
                        <td class="text-end pe-4">
                            {{-- Ikon mata: lihat detail SELURUH siklus batch ini.
                                 Read-only, jadi tetap tampil untuk akun demo. --}}
                            <button type="button"
                                    class="btn btn-sm btn-light border text-info me-1"
                                    title="Lihat detail batch"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalDetailBatch"
                                    data-url="{{ route('semai.batch', $batch) }}">
                                <i class="fas fa-eye"></i>
                            </button>
                            @unless(auth()->user()->is_demo)
                                {{-- Satu tombol aksi per sub-baris (per meja) --}}
                                @foreach($sub as $s)
                                    @php
                                        $idSub = $tahapAktif === 'semai' ? $s->id_semai
                                            : ($tahapAktif === 'peremajaan' ? $s->id_peremajaan
                                            : ($tahapAktif === 'pendewasaan' ? $s->id_pendewasaan : $s->id_panen));
                                    @endphp
                                    <span class="d-inline-flex align-items-center gap-1 me-1">
                                        @if($jmlMeja > 1)
                                            <small class="text-muted">{{ $s->meja?->meja ?? '-' }}</small>
                                        @endif
                                        <span class="btn-group btn-group-sm">
                                            <a href="{{ route($routeTahap.'.edit', $idSub) }}" class="btn btn-light border text-secondary" title="Edit"><i class="fas fa-pen-to-square"></i></a>
                                            <form action="{{ route($routeTahap.'.destroy', $idSub) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $konfirmasi }}')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-light border text-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </span>
                                    </span>
                                @endforeach
                            @endunless
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Data {{ $labelTahap[$tahapAktif] }}</h6>
                            <p class="small text-muted mb-0">Data tahap {{ $labelTahap[$tahapAktif] }} belum ada pada filter ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- PAGINATION GAYA GOOGLE: nomor halaman + link Next --}}
    @if($rows->hasPages())
    @php
        $cur = $rows->currentPage();
        $last = $rows->lastPage();
        // Jendela maksimal 10 nomor (seperti gambar): 1 2 3 ... 10
        $span = 10;
        if ($last <= $span) {
            $mulai = 1; $selesai = $last;
        } else {
            $mulai = max(1, $cur - 4);
            $selesai = min($last, $mulai + $span - 1);
            $mulai = max(1, $selesai - $span + 1);
        }
    @endphp
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-4 pb-3 pt-2 border-top">
        <small class="text-muted">Halaman {{ $cur }} dari {{ $last }} · {{ $rows->total() }} data</small>
        <nav class="gp-nav" aria-label="Navigasi halaman">
            <div class="gp-row">
                @if(! $rows->onFirstPage())
                    <a class="gp-link" href="{{ $rows->previousPageUrl() }}#">&lsaquo; Prev</a>
                @endif
                @for($p = $mulai; $p <= $selesai; $p++)
                    @if($p == $cur)
                        <span class="gp-page gp-active">{{ $p }}</span>
                    @else
                        <a class="gp-page" href="{{ $rows->url($p) }}">{{ $p }}</a>
                    @endif
                @endfor
                @if($rows->hasMorePages())
                    <a class="gp-link gp-next" href="{{ $rows->nextPageUrl() }}#">Next &rsaquo;</a>
                @endif
            </div>
        </nav>
    </div>
    @endif
</div>

{{-- MODAL DETAIL BATCH (isi diisi via AJAX dari tombol mata) --}}
<div class="modal fade" id="modalDetailBatch" tabindex="-1" aria-labelledby="judulDetailBatch" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="judulDetailBatch">
                    <i class="fas fa-eye me-2 text-info"></i>Detail Batch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="isiDetailBatch">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="small text-muted mt-2 mb-0">Memuat detail batch…</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-bs-target="#modalDetailBatch"]');
    if (!btn) return;

    const body = document.getElementById('isiDetailBatch');
    body.innerHTML =
        '<div class="text-center py-5">' +
        '<div class="spinner-border text-primary" role="status"></div>' +
        '<p class="small text-muted mt-2 mb-0">Memuat detail batch…</p></div>';

    fetch(btn.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(function (html) { body.innerHTML = html; })
        .catch(function () {
            body.innerHTML =
                '<div class="alert alert-danger mb-0">' +
                '<i class="fas fa-triangle-exclamation me-1"></i>' +
                'Gagal memuat detail batch. Muat ulang halaman lalu coba lagi.</div>';
        });
});
</script>

@push('styles')
<style>
    .gp-nav .gp-row { display: flex; align-items: center; gap: 2px; flex-wrap: wrap; }
    .gp-nav .gp-page {
        color: #1a0dab; text-decoration: none; padding: 3px 9px;
        font-size: 15px; border-radius: 4px; line-height: 1.6;
    }
    .gp-nav .gp-page:hover { text-decoration: underline; background: #f1f3f4; }
    .gp-nav .gp-active {
        color: #000; font-weight: 700; background: transparent;
        cursor: default; text-decoration: none;
    }
    .gp-nav .gp-link {
        color: #1a0dab; text-decoration: underline; padding: 3px 9px;
        font-size: 15px; border-radius: 4px; line-height: 1.6;
    }
    .gp-nav .gp-link:hover { background: #f1f3f4; }
    .gp-nav .gp-next { margin-left: 12px; }
    @media (max-width: 575.98px) {
        .gp-nav .gp-page, .gp-nav .gp-link { font-size: 14px; padding: 3px 6px; }
    }
</style>
@endpush
@endsection
