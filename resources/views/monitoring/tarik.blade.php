@extends('layouts.app')
@section('title', 'Tarik Data Sensor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-download me-2 text-primary"></i>Tarik Data Sensor</h4>
        <p class="text-muted small mb-0">Pilih rentang tanggal lalu unduh rekaman sensor sebagai CSV atau PDF.</p>
    </div>
    <a href="{{ route('monitoring.index') }}" class="btn btn-light border no-print">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<!-- FORM PENARIKAN -->
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="fas fa-filter me-1 text-primary"></i> Rentang Data</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('monitoring.tarik') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Dari Tanggal</label>
                <input type="date" name="dari" value="{{ $dari }}" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Sampai Tanggal</label>
                <input type="date" name="sampai" value="{{ $sampai }}" class="form-control" required>
            </div>
                <div class="col-md-6 d-flex gap-2 align-items-end">
                    <div>
                        <label class="form-label small fw-bold mb-0">Baris/hal</label>
                        <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ (int) request('per_page', 10) === $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                    <a href="{{ route('monitoring.tarik.csv', request()->query()) }}" class="btn btn-outline-success">
                        <i class="fas fa-file-csv me-1"></i> Unduh CSV
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-outline-danger"
                            title="Pilih tujuan Save as PDF untuk menyimpan PDF">
                        <i class="fas fa-file-pdf me-1"></i> Unduh PDF / Cetak
                    </button>
                </div>
        </form>
    </div>
</div>

<!-- PRATINJAU DATA -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-primary"><i class="fas fa-table me-2"></i>Rekaman Sensor {{ $dari }} s/d {{ $sampai }}</h5>
        <span class="badge badge-soft-info">{{ number_format($data->total()) }} baris
            @if($data->total() > $data->count())
                <span class="text-muted">(menampilkan {{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }})</span>
            @endif
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 text-center">
            <thead class="table-light">
                <tr>
                    <th class="text-start ps-3">Waktu</th>
                    <th>Suhu Air (°C)</th>
                    <th>pH</th>
                    <th>TDS (ppm)</th>
                    <th>Suhu Udara (°C)</th>
                    <th>Kelembapan (%)</th>
                    <th>Level Tandon</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $d)
                <tr>
                    <td class="text-start ps-3">{{ \Carbon\Carbon::parse($d->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $d->waterTemp !== null ? number_format($d->waterTemp, 1) : '—' }}</td>
                    <td>{{ $d->ph !== null ? number_format($d->ph, 2) : '—' }}</td>
                    <td>{{ $d->tds !== null ? number_format($d->tds) : '—' }}</td>
                    <td>{{ $d->airTemp !== null ? number_format($d->airTemp, 1) : '—' }}</td>
                    <td>{{ $d->humidity !== null ? number_format($d->humidity, 1) : '—' }}</td>
                    <td>{{ $d->water_level ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-muted py-4">Tidak ada data sensor pada rentang tanggal ini</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-muted">
        Tabel menampilkan <strong>{{ $data->count() }} dari {{ number_format($data->total()) }} baris</strong> per halaman.
        Tombol <em>Unduh CSV</em> tetap mengambil seluruh rentang (maksimal 5.000 baris).
    </div>
</div>

{{-- PAGINASI GAYA GOOGLE --}}
@if($data->hasPages())
<nav class="mt-3 d-flex flex-wrap justify-content-between align-items-center gap-2" aria-label="Navigasi halaman">
    <div class="text-muted small">
        Menampilkan <strong>{{ number_format($data->firstItem() ?? 0) }}</strong>–<strong>{{ number_format($data->lastItem() ?? 0) }}</strong>
        dari <strong>{{ number_format($data->total()) }}</strong> rekaman
    </div>
    <ul class="pagination pagination-sm mb-0 flex-wrap">
        {{-- Prev --}}
        @if($data->onFirstPage())
            <li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-left"></i></span></li>
        @else
            <li class="page-item"><a class="page-link" href="{{ $data->previousPageUrl() }}"><i class="fas fa-chevron-left"></i></a></li>
        @endif

        {{-- Nomor halaman (jendela ±5, dua titik penghubung seperti Google) --}}
        @php $titik = false; @endphp
        @for($i = 1; $i <= $data->lastPage(); $i++)
            @if($i === 1 || $i === $data->lastPage() || abs($i - $data->currentPage()) <= 4)
                @php $titik = false; @endphp
                <li class="page-item {{ $i === $data->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $data->url($i) }}">{{ $i }}</a>
                </li>
            @else
                @if(!$titik)
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                    @php $titik = true; @endphp
                @endif
            @endif
        @endfor

        {{-- Next --}}
        @if($data->hasMorePages())
            <li class="page-item"><a class="page-link" href="{{ $data->nextPageUrl() }}"><i class="fas fa-chevron-right"></i></a></li>
        @else
            <li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-right"></i></span></li>
        @endif
    </ul>
</nav>
@endif

<style>
@media print {
    .sidebar, .sidebar-backdrop, .topbar, .no-print, form { display: none !important; }
    /* Harus .main-wrapper (bukan .main-content) supaya margin-left 260px
       hilang — kalau tidak, hasil PDF terpotong di sebelah kanan. */
    .main-wrapper { margin-left: 0 !important; }
    .content-body { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
    .table-responsive { overflow: visible !important; }
    table { width: 100% !important; }
    @page { margin: 12mm; }
}
</style>
@endsection
