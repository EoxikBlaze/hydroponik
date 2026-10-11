{{-- ============================================================
     FRAGMEN DETAIL SATU BATCH (dimuat via AJAX oleh ikon mata)
     Menampilkan seluruh jejak siklus: Semai -> Peremajaan ->
     Pendewasaan -> Panen, lengkap dengan sub-baris hasil split
     multi-meja. File ini sengaja BUKAN layout utama karena hanya
     ditempel ke dalam bodi modal.
     ============================================================ --}}
@php
    $tgl = fn (?string $t) => $t ? \Carbon\Carbon::parse($t)->format('d M Y') : '—';
    $rentang = fn (?string $a, ?string $b) => $a ? $tgl($a).($b ? ' → '.$tgl($b) : '') : '—';

    $rute = function (string $label) {
        return [
            'semai'       => ['route' => 'semai.edit',      'param' => 'id_semai'],
            'peremajaan'  => ['route' => 'peremajaan.edit', 'param' => 'id_peremajaan'],
            'pendewasaan' => ['route' => 'pendewasaan.edit','param' => 'id_pendewasaan'],
            'panen'       => ['route' => 'panen.edit',      'param' => 'id_panen'],
        ][$label];
    };
@endphp

{{-- HEADER BATCH --}}
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="mb-1 text-dark">
            <i class="fas fa-layer-group me-2 text-primary"></i>
            Batch <strong>#{{ $semai->id_semai }}</strong>
        </h5>
        <div class="text-muted small">
            {{ $semai->tanaman?->nama_tanaman ?? '—' }}
            · Siklus <strong>{{ $semai->siklus?->nama_siklus ?? '—' }}</strong>
            ({{ $semai->siklus?->waktu_siklus ?? '—' }} hari)
            · mulai {{ $tgl($semai->tgl_awal_semai) }}
        </div>
    </div>
    <div class="text-end">
        @if($semai->status)
            <span class="badge {{ $semai->status === 'selesai' ? 'bg-success' : 'bg-warning text-dark' }} px-2 py-1">
                {{ ucfirst($semai->status) }}
            </span>
        @endif
        <div class="small text-muted mt-1">Benih {{ $semai->jumlah_benih }} · lolos {{ $semai->benih_berhasil }}</div>
    </div>
</div>

@if($semai->keterangan)
    <div class="alert alert-light border small py-2 mb-3"><i class="far fa-comment-dots me-1"></i>{{ $semai->keterangan }}</div>
@endif

{{-- 1 · SEMAI --}}
<div class="border rounded-3 overflow-hidden mb-3">
    <div class="bg-light px-3 py-2 d-flex justify-content-between align-items-center">
        <strong class="small"><i class="fas fa-seedling me-2 text-success"></i>1 · Semai</strong>
        <div class="d-flex align-items-center gap-2">
            <span class="badge badge-soft-secondary">1 baris</span>
            @unless(auth()->user()->is_demo)
                <a href="{{ route('semai.edit', $semai->id_semai) }}" class="btn btn-sm btn-light border" title="Edit"><i class="fas fa-pen-to-square"></i></a>
            @endunless
        </div>
    </div>
    <table class="table table-sm align-middle mb-0 text-center small">
        <thead class="table-light">
            <tr>
                <th class="text-start ps-3">Meja</th>
                <th>Masuk</th><th>Lolos</th><th>Gagal</th>
                <th>Periode</th><th class="text-start ps-3">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-start ps-3"><span class="badge badge-soft-info">{{ $semai->meja?->meja ?? '—' }}</span></td>
                <td>{{ $semai->jumlah_benih }}</td>
                <td><span class="badge badge-soft-success">{{ $semai->benih_berhasil }}</span></td>
                <td><span class="badge badge-soft-danger">{{ $semai->benih_gagal }}</span></td>
                <td class="small">{{ $rentang($semai->tgl_awal_semai, $semai->tgl_akhir_semai) }}</td>
                <td class="text-start ps-3 small text-muted">{{ $semai->keterangan ?: '—' }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- 2 · PEREMAJAAN --}}
<div class="border rounded-3 overflow-hidden mb-3">
    <div class="bg-light px-3 py-2 d-flex justify-content-between align-items-center">
        <strong class="small"><i class="fas fa-arrows-rotate me-2 text-primary"></i>2 · Peremajaan</strong>
        <span class="badge badge-soft-secondary">{{ $pers->count() }} baris</span>
    </div>
    @if($pers->isEmpty())
        <div class="p-3 text-center small text-muted py-4">
            <i class="far fa-circle-question me-1"></i> Tahap ini belum dimulai — batch masih di tahap Semai.
        </div>
    @else
        <table class="table table-sm align-middle mb-0 text-center small">
            <thead class="table-light">
                <tr>
                    <th class="text-start ps-3">Meja</th>
                    <th>Masuk</th><th>Lolos</th><th>Gagal</th>
                    <th>Periode</th><th class="text-start ps-3">Keterangan</th>
                    @unless(auth()->user()->is_demo)<th class="pe-3" style="width:70px;">Aksi</th>@endunless
                </tr>
            </thead>
            <tbody>
                @foreach($pers as $p)
                <tr>
                    <td class="text-start ps-3"><span class="badge badge-soft-info">{{ $p->meja?->meja ?? '—' }}</span></td>
                    <td>{{ $p->jumlah_masuk ?? '—' }}</td>
                    <td><span class="badge badge-soft-success">{{ $p->benih_berhasil }}</span></td>
                    <td><span class="badge badge-soft-danger">{{ $p->benih_gagal }}</span></td>
                    <td class="small">{{ $rentang($p->tgl_awal_peremajaan, $p->tgl_akhir_peremajaan) }}</td>
                    <td class="text-start ps-3 small text-muted">{{ $p->keterangan ?: '—' }}</td>
                    @unless(auth()->user()->is_demo)
                        <td class="pe-3">
                            <a href="{{ route('peremajaan.edit', $p->id_peremajaan) }}" class="btn btn-sm btn-light border" title="Edit"><i class="fas fa-pen-to-square"></i></a>
                        </td>
                    @endunless
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- 3 · PENDEWASAAN --}}
<div class="border rounded-3 overflow-hidden mb-3">
    <div class="bg-light px-3 py-2 d-flex justify-content-between align-items-center">
        <strong class="small"><i class="fas fa-leaf me-2 text-success"></i>3 · Pendewasaan</strong>
        <span class="badge badge-soft-secondary">{{ $pens->count() }} baris</span>
    </div>
    @if($pens->isEmpty())
        <div class="p-3 text-center small text-muted py-4">
            <i class="far fa-circle-question me-1"></i> Tahap ini belum dimulai.
        </div>
    @else
        <table class="table table-sm align-middle mb-0 text-center small">
            <thead class="table-light">
                <tr>
                    <th class="text-start ps-3">Meja</th>
                    <th>Masuk</th><th>Lolos</th><th>Gagal</th>
                    <th>Periode</th><th class="text-start ps-3">Keterangan</th>
                    @unless(auth()->user()->is_demo)<th class="pe-3" style="width:70px;">Aksi</th>@endunless
                </tr>
            </thead>
            <tbody>
                @foreach($pens as $pn)
                <tr>
                    <td class="text-start ps-3"><span class="badge badge-soft-info">{{ $pn->meja?->meja ?? '—' }}</span></td>
                    <td>{{ $pn->jumlah_masuk ?? '—' }}</td>
                    <td><span class="badge badge-soft-success">{{ $pn->tanaman_berhasil }}</span></td>
                    <td><span class="badge badge-soft-danger">{{ $pn->tanaman_gagal }}</span></td>
                    <td class="small">{{ $rentang($pn->tgl_awal_pendewasaan, $pn->tgl_akhir_pendewasaan) }}</td>
                    <td class="text-start ps-3 small text-muted">{{ $pn->keterangan ?: '—' }}</td>
                    @unless(auth()->user()->is_demo)
                        <td class="pe-3">
                            <a href="{{ route('pendewasaan.edit', $pn->id_pendewasaan) }}" class="btn btn-sm btn-light border" title="Edit"><i class="fas fa-pen-to-square"></i></a>
                        </td>
                    @endunless
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- 4 · PANEN --}}
<div class="border rounded-3 overflow-hidden mb-1">
    <div class="bg-light px-3 py-2 d-flex justify-content-between align-items-center">
        <strong class="small"><i class="fas fa-basket-shopping me-2 text-warning"></i>4 · Panen</strong>
        <span class="badge badge-soft-secondary">{{ $panens->count() }} baris</span>
    </div>
    @if($panens->isEmpty())
        <div class="p-3 text-center small text-muted py-4">
            <i class="far fa-circle-question me-1"></i> Tahap ini belum dimulai.
        </div>
    @else
        <table class="table table-sm align-middle mb-0 text-center small">
            <thead class="table-light">
                <tr>
                    <th class="text-start ps-3">Meja</th>
                    <th>Tgl Panen</th><th>Panen Berhasil</th><th>Gagal</th>
                    <th class="text-start ps-3">Keterangan</th>
                    @unless(auth()->user()->is_demo)<th class="pe-3" style="width:70px;">Aksi</th>@endunless
                </tr>
            </thead>
            <tbody>
                @foreach($panens as $pn)
                <tr>
                    {{-- Meja diambil dari induk pendewasaan karena tabel panen
                         tidak menyimpan id_meja sendiri. --}}
                    <td class="text-start ps-3"><span class="badge badge-soft-info">{{ $pn->pendewasaan?->meja?->meja ?? '—' }}</span></td>
                    <td class="small">{{ $tgl($pn->tgl_panen) }}</td>
                    <td><span class="badge badge-soft-success">{{ $pn->panen_berhasil }}</span></td>
                    <td><span class="badge badge-soft-danger">{{ $pn->panen_gagal }}</span></td>
                    <td class="text-start ps-3 small text-muted">{{ $pn->keterangan ?: '—' }}</td>
                    @unless(auth()->user()->is_demo)
                        <td class="pe-3">
                            <a href="{{ route('panen.edit', $pn->id_panen) }}" class="btn btn-sm btn-light border" title="Edit"><i class="fas fa-pen-to-square"></i></a>
                        </td>
                    @endunless
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- RINGKASAN AKHIR --}}
@php
    $jmlPer  = $pers->count();
    $jmlPend = $pens->count();
    $jmlPanen = $panens->count();
    $totalPanen = $panens->sum('panen_berhasil');
@endphp
<div class="small text-muted mt-3 pt-2 border-top d-flex flex-wrap gap-3">
    <span><i class="fas fa-layer-group me-1"></i> Total {{ $jmlPer }} baris peremajaan</span>
    <span><i class="fas fa-leaf me-1"></i> {{ $jmlPend }} baris pendewasaan</span>
    <span><i class="fas fa-basket-shopping me-1"></i> {{ $jmlPanen }} baris panen</span>
    @if($jmlPanen > 0)
        <span class="text-success"><i class="fas fa-check-circle me-1"></i> Hasil panen total {{ $totalPanen }}</span>
    @endif
</div>
