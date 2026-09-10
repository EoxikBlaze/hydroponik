@extends('layouts.app')
@section('title', 'Data Pendewasaan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-leaf me-2 text-primary"></i>Tahap 3: Data Pendewasaan Tanaman</h4>
        <p class="text-muted small mb-0">Tahap akhir pembesaran vegetatif pada meja pendewasaan hingga tanaman siap dipanen</p>
    </div>
    <a href="{{ route('pendewasaan.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
        <i class="fas fa-plus me-1"></i> Tambah Pendewasaan
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Tanaman</th>
                        <th>Meja Pendewasaan</th>
                        <th>Populasi Tanaman</th>
                        <th>Periode Pendewasaan</th>
                        <th>Status Notif</th>
                        <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendewasaans as $d)
                    <tr>
                        <td class="ps-4 fw-bold text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <span class="fw-bold text-dark d-block">{{ $d->peremajaan?->semai?->tanaman?->nama_tanaman ?? '-' }}</span>
                            <small class="text-muted">{{ $d->keterangan ? Str::limit($d->keterangan, 30) : '-' }}</small>
                        </td>
                        <td>
                            <span class="badge badge-soft-info"><i class="fas fa-table-cells-large me-1"></i>{{ $d->meja?->meja ?? '-' }}</span>
                            <small class="d-block text-muted">{{ $d->meja?->jumlah_lubang ?? '0' }} Lubang</small>
                        </td>
                        <td>
                            <div class="fw-bold">
                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>{{ $d->tanaman_berhasil }} Sehat</span>
                            </div>
                            <small class="text-danger"><i class="fas fa-times-circle me-1"></i>{{ $d->tanaman_gagal }} Rusak/Kerdil</small>
                        </td>
                        <td>
                            <span class="d-block small text-dark"><i class="far fa-calendar me-1 text-muted"></i>{{ \Carbon\Carbon::parse($d->tgl_awal_pendewasaan)->format('d M Y') }}</span>
                            <span class="d-block small text-muted"><i class="far fa-calendar-check me-1 text-muted"></i>Panen: {{ \Carbon\Carbon::parse($d->tgl_akhir_pendewasaan)->format('d M Y') }}</span>
                        </td>
                        <td>
                            @if($d->status_notif === 'sent')
                                <span class="badge badge-soft-success"><i class="fab fa-whatsapp me-1"></i>Terkirim</span>
                            @else
                                <span class="badge badge-soft-warning"><i class="far fa-clock me-1"></i>Pending</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('pendewasaan.edit', $d->id_pendewasaan) }}" class="btn btn-light border text-secondary" title="Edit Data">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('pendewasaan.destroy', $d->id_pendewasaan) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data pendewasaan ini?')">
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
                            <i class="fas fa-leaf fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Data Pendewasaan</h6>
                            <p class="small text-muted mb-3">Pindahkan tanaman dari tahap peremajaan ke meja pendewasaan.</p>
                            <a href="{{ route('pendewasaan.create') }}" class="btn btn-emerald btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Pendewasaan
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
