@extends('layouts.app')
@section('title', 'Hasil Panen')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-wheat-awn me-2 text-warning"></i>Tahap 4: Pencatatan Hasil Panen</h4>
        <p class="text-muted small mb-0">Dokumentasi hasil panen sayuran hidroponik, tingkat kelayakan konsumsi & afkir</p>
    </div>
    <a href="{{ route('panen.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
        <i class="fas fa-plus me-1"></i> Catat Panen Baru
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Tanaman / Komoditas</th>
                        <th>Tanggal Panen</th>
                        <th>Hasil Layak (Sukses)</th>
                        <th>Gagal / Afkir</th>
                        <th>Keterangan</th>
                        <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($panens as $p)
                    <tr>
                        <td class="ps-4 fw-bold text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <span class="fw-bold text-dark d-block">{{ $p->pendewasaan?->peremajaan?->semai?->tanaman?->nama_tanaman ?? '-' }}</span>
                            <small class="text-muted">ID Pendewasaan #{{ $p->id_pendewasaan }}</small>
                        </td>
                        <td>
                            <i class="far fa-calendar-check me-1 text-success"></i>
                            <span class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($p->tgl_panen)->format('d F Y') }}</span>
                        </td>
                        <td>
                            <span class="badge badge-soft-success px-3 py-2 fs-6">
                                <i class="fas fa-circle-check me-1"></i>{{ $p->panen_berhasil }} Ikat/Pcs
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-soft-danger px-2 py-1">
                                {{ $p->panen_gagal }} Rusak
                            </span>
                        </td>
                        <td>
                            <span class="small text-muted">{{ $p->keterangan ? $p->keterangan : '-' }}</span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('panen.edit', $p->id_panen) }}" class="btn btn-light border text-secondary" title="Edit Data">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('panen.destroy', $p->id_panen) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data panen ini?')">
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
                            <i class="fas fa-wheat-awn fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Catatan Panen</h6>
                            <p class="small text-muted mb-3">Catat hasil panen ketika tanaman pada tahap pendewasaan telah dipetik.</p>
                            <a href="{{ route('panen.create') }}" class="btn btn-emerald btn-sm">
                                <i class="fas fa-plus me-1"></i> Catat Panen Pertama
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
