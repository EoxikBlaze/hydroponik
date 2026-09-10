@extends('layouts.app')
@section('title', 'Laporan Hasil Siklus & Sensor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="mb-0">📋 Laporan Hasil Siklus Hidroponik</h3>
        <p class="text-muted mb-0">Statistik performa siklus tanam, panen, serta ringkasan kondisi air & suhu</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fas fa-print me-1"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- FILTER CARD -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="fas fa-filter me-1 text-primary"></i> Filter Laporan</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('laporan.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Tanaman</label>
                    <select name="id_tanaman" class="form-select">
                        <option value="">Semua Tanaman</option>
                        @foreach($tanaman_list as $tanaman)
                            <option value="{{ $tanaman->id_tanaman }}" {{ $filter['id_tanaman'] == $tanaman->id_tanaman ? 'selected' : '' }}>
                                {{ $tanaman->nama_tanaman }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold">Periode</label>
                    <select name="periode" id="periode" class="form-select">
                        <option value="minggu" {{ $filter['periode'] == 'minggu' ? 'selected' : '' }}>Per Minggu</option>
                        <option value="bulan"  {{ $filter['periode'] == 'bulan'  ? 'selected' : '' }}>Per Bulan</option>
                        <option value="tahun"  {{ $filter['periode'] == 'tahun'  ? 'selected' : '' }}>Per Tahun</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold">Tahun</label>
                    <select name="tahun" class="form-select">
                        @foreach($tahun_list as $thn)
                            <option value="{{ $thn }}" {{ $filter['tahun'] == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2" id="bulan-filter" style="{{ $filter['periode'] == 'bulan' ? '' : 'display:none;' }}">
                    <label class="form-label small fw-bold">Bulan</label>
                    <select name="bulan" class="form-select">
                        @for($i = 1; $i <= 12; $i++)
                            @php $namaBulan = \Carbon\Carbon::create(null, $i, 1)->locale('id')->isoFormat('MMMM'); @endphp
                            <option value="{{ $i }}" {{ $filter['bulan'] == $i ? 'selected' : '' }}>{{ $namaBulan }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2" id="minggu-filter" style="{{ $filter['periode'] == 'minggu' ? '' : 'display:none;' }}">
                    <label class="form-label small fw-bold">Minggu Ke-</label>
                    <select name="minggu" class="form-select">
                        @for($i = 1; $i <= 53; $i++)
                            <option value="{{ $i }}" {{ $filter['minggu'] == $i ? 'selected' : '' }}>Minggu {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MATRIKS LAPORAN SIKLUS -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-success"><i class="fas fa-seedling me-2"></i>Matriks Keberhasilan Tahapan Siklus</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 text-center">
            <thead class="table-light">
                <tr>
                    <th rowspan="3" class="align-middle text-start ps-3" style="min-width: 150px;">TANAMAN</th>
                    <th colspan="8" class="bg-light fw-bold text-uppercase">Tahapan Siklus Tanam</th>
                </tr>
                <tr>
                    <th colspan="2" class="table-info">SEMAI</th>
                    <th colspan="2" class="table-warning">PEREMAJAAN</th>
                    <th colspan="2" class="table-primary">PENDEWASAAN</th>
                    <th colspan="2" class="table-success">PANEN</th>
                </tr>
                <tr class="small text-muted">
                    <th class="table-info">BERHASIL</th>
                    <th class="table-info">GAGAL</th>
                    <th class="table-warning">BERHASIL</th>
                    <th class="table-warning">GAGAL</th>
                    <th class="table-primary">BERHASIL</th>
                    <th class="table-primary">GAGAL</th>
                    <th class="table-success">BERHASIL</th>
                    <th class="table-success">GAGAL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($laporan as $item)
                <tr>
                    <td class="text-start ps-3 fw-bold">{{ $item['nama_tanaman'] }}</td>
                    <td class="text-success fw-bold">{{ $item['semai_berhasil'] }}</td>
                    <td class="text-danger">{{ $item['semai_gagal'] }}</td>
                    <td class="text-success fw-bold">{{ $item['peremajaan_berhasil'] }}</td>
                    <td class="text-danger">{{ $item['peremajaan_gagal'] }}</td>
                    <td class="text-success fw-bold">{{ $item['pendewasaan_berhasil'] }}</td>
                    <td class="text-danger">{{ $item['pendewasaan_gagal'] }}</td>
                    <td class="text-success fw-bold">{{ $item['panen_berhasil'] }}</td>
                    <td class="text-danger">{{ $item['panen_gagal'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-muted py-4">Belum ada data pada periode ini</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="table-secondary fw-bold">
                <tr>
                    <td class="text-start ps-3">TOTAL KESELURUHAN</td>
                    <td class="text-success">{{ $total['semai_berhasil'] }}</td>
                    <td class="text-danger">{{ $total['semai_gagal'] }}</td>
                    <td class="text-success">{{ $total['peremajaan_berhasil'] }}</td>
                    <td class="text-danger">{{ $total['peremajaan_gagal'] }}</td>
                    <td class="text-success">{{ $total['pendewasaan_berhasil'] }}</td>
                    <td class="text-danger">{{ $total['pendewasaan_gagal'] }}</td>
                    <td class="text-success">{{ $total['panen_berhasil'] }}</td>
                    <td class="text-danger">{{ $total['panen_gagal'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- IOT SENSOR & ALERTS RINGKASAN -->
<div class="row g-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-info"><i class="fas fa-satellite-dish me-2"></i>Rata-Rata Kondisi Air & Suhu (6 Jam Terakhir)</h6>
            </div>
            <div class="card-body">
                @if($sensor)
                <table class="table table-hover mb-0">
                    <tbody>
                        <tr><td>🌡️ Suhu Air Nutrisi</td><td class="fw-bold text-end">{{ $sensor['waterTemp'] }} °C</td></tr>
                        <tr><td>🧪 pH Air Nutrisi</td><td class="fw-bold text-end">{{ $sensor['ph'] }}</td></tr>
                        <tr><td>⚡ TDS Nutrisi</td><td class="fw-bold text-end">{{ $sensor['tds'] }} ppm</td></tr>
                        <tr><td>🌤️ Suhu Udara Greenhouse</td><td class="fw-bold text-end">{{ $sensor['airTemp'] }} °C</td></tr>
                        <tr><td>💧 Kelembapan Udara</td><td class="fw-bold text-end">{{ $sensor['humidity'] }} %</td></tr>
                    </tbody>
                </table>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                    Belum ada data sensor dalam 6 jam terakhir.
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-light">
                <h6 class="mb-0 text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Riwayat Peringatan Masalah (6 Jam Terakhir)</h6>
            </div>
            <div class="card-body">
                @if(!empty($alerts))
                <ul class="list-group list-group-flush">
                    @foreach($alerts as $a)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ ucfirst($a['type']) }}</strong>
                            <small class="text-muted d-block">{{ $a['created_at'] ?? now() }}</small>
                        </div>
                        <span class="badge bg-danger">{{ $a['value'] }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center py-4 text-success">
                    <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                    Semua sensor berada dalam kondisi normal!
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const periodeSelect = document.getElementById('periode');
    const bulanFilter   = document.getElementById('bulan-filter');
    const mingguFilter  = document.getElementById('minggu-filter');

    if (periodeSelect) {
        periodeSelect.addEventListener('change', function () {
            if (this.value === 'bulan') {
                bulanFilter.style.display  = 'block';
                mingguFilter.style.display = 'none';
            } else if (this.value === 'minggu') {
                bulanFilter.style.display  = 'none';
                mingguFilter.style.display = 'block';
            } else {
                bulanFilter.style.display  = 'none';
                mingguFilter.style.display = 'none';
            }
        });
    }
});
</script>
@endsection
