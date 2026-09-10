@extends('layouts.app')
@section('title', 'Data Peremajaan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-spa me-2 text-warning"></i>Tahap 2: Data Peremajaan Tanaman</h4>
        <p class="text-muted small mb-0">Pembesaran bibit pada meja peremajaan hidroponik sebelum masuk tahap pendewasaan</p>
    </div>
    <a href="{{ route('peremajaan.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
        <i class="fas fa-plus me-1"></i> Tambah Peremajaan
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Tanaman & Semai Asal</th>
                        <th>Meja Alokasi</th>
                        <th>Hasil Bibit</th>
                        <th>Periode Peremajaan</th>
                        <th>Status Notif</th>
                        <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($peremajaans as $p)
                    <tr>
                        <td class="ps-4 fw-bold text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <span class="fw-bold text-dark d-block">{{ $p->semai?->tanaman?->nama_tanaman ?? '-' }}</span>
                            <small class="text-muted">Batch Semai #{{ $p->id_semai }} • {{ $p->keterangan ? Str::limit($p->keterangan, 25) : '-' }}</small>
                        </td>
                        <td>
                            <span class="badge badge-soft-primary"><i class="fas fa-table-cells-large me-1"></i>{{ $p->meja?->meja ?? '-' }}</span>
                            <small class="d-block text-muted">{{ $p->meja?->jumlah_lubang ?? '0' }} Lubang Tanam</small>
                        </td>
                        <td>
                            <div class="fw-bold">
                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>{{ $p->benih_berhasil }} Hidup</span>
                            </div>
                            <small class="text-danger"><i class="fas fa-times-circle me-1"></i>{{ $p->benih_gagal }} Gagal</small>
                        </td>
                        <td>
                            <span class="d-block small text-dark"><i class="far fa-calendar me-1 text-muted"></i>{{ \Carbon\Carbon::parse($p->tgl_awal_peremajaan)->format('d M Y') }}</span>
                            <span class="d-block small text-muted"><i class="far fa-calendar-check me-1 text-muted"></i>s/d {{ \Carbon\Carbon::parse($p->tgl_akhir_peremajaan)->format('d M Y') }}</span>
                        </td>
                        <td>
                            @if($p->status_notif === 'sent')
                                <span class="badge badge-soft-success"><i class="fab fa-whatsapp me-1"></i>Terkirim</span>
                            @else
                                <span class="badge badge-soft-warning"><i class="far fa-clock me-1"></i>Pending</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('peremajaan.edit', $p->id_peremajaan) }}" class="btn btn-light border text-secondary" title="Edit Data">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('peremajaan.destroy', $p->id_peremajaan) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data peremajaan ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-light border text-danger" title="Hapus Data">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-spa fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Data Peremajaan</h6>
                            <p class="small text-muted mb-3">Pindahkan bibit yang telah selesai disemai ke tahap peremajaan.</p>
                            <a href="{{ route('peremajaan.create') }}" class="btn btn-emerald btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Peremajaan
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
