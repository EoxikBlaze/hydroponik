@extends('layouts.app')
@section('title', 'Data Penyemaian')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-seedling me-2 text-success"></i>Tahap 1: Data Penyemaian Benih</h4>
        <p class="text-muted small mb-0">Kelola dan pantau proses pembibitan sebelum dipindahkan ke meja peremajaan</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('semai.selesai') }}" class="btn btn-outline-secondary btn-sm px-3 py-2">
            <i class="fas fa-list-check me-1"></i> Riwayat Selesai
        </a>
        <a href="{{ route('semai.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
            <i class="fas fa-plus me-1"></i> Mulai Semai Baru
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Varietas Tanaman</th>
                        <th>Durasi Siklus</th>
                        <th>Alokasi Benih</th>
                        <th>Periode Semai</th>
                        <th>Status Notif WA</th>
                        <th>Status Tahap</th>
                        <th class="text-end pe-4" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($semais as $s)
                    <tr>
                        <td class="ps-4 fw-bold text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <span class="fw-bold text-dark d-block">{{ $s->tanaman?->nama_tanaman ?? '-' }}</span>
                            <small class="text-muted">{{ $s->keterangan ? Str::limit($s->keterangan, 30) : 'Tanpa catatan' }}</small>
                        </td>
                        <td>
                            <span class="badge badge-soft-info">{{ $s->siklus?->nama_siklus ?? 'Semai' }}</span>
                            <small class="d-block text-muted">{{ $s->siklus?->waktu_siklus ?? '5' }} Hari</small>
                        </td>
                        <td>
                            <span class="fw-bold text-dark">{{ $s->jumlah_benih }}</span> benih
                            <div class="small">
                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>{{ $s->benih_berhasil }}</span>
                                <span class="text-danger ms-2"><i class="fas fa-times-circle me-1"></i>{{ $s->benih_gagal }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="d-block small text-dark"><i class="far fa-calendar me-1 text-muted"></i>{{ \Carbon\Carbon::parse($s->tgl_awal_semai)->format('d M Y') }}</span>
                            <span class="d-block small text-muted"><i class="far fa-calendar-check me-1 text-muted"></i>s/d {{ \Carbon\Carbon::parse($s->tgl_akhir_semai)->format('d M Y') }}</span>
                        </td>
                        <td>
                            @if($s->status_notif === 'sent')
                                <span class="badge badge-soft-success"><i class="fab fa-whatsapp me-1"></i>Terkirim</span>
                            @else
                                <span class="badge badge-soft-warning"><i class="far fa-clock me-1"></i>Pending</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $badgeClass = match($s->status) {
                                    'selesai' => 'badge-soft-success',
                                    'proses'  => 'badge-soft-primary',
                                    'gagal'   => 'badge-soft-danger',
                                    default   => 'badge-soft-warning'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }} px-2 py-1 text-uppercase">{{ $s->status }}</span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('semai.edit', $s->id_semai) }}" class="btn btn-light border text-secondary" title="Edit Data">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('semai.destroy', $s->id_semai) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data semai ini?')">
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
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-seedling fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Batch Penyemaian</h6>
                            <p class="small text-muted mb-3">Mulai proses pembibitan dengan mendaftarkan benih baru.</p>
                            <a href="{{ route('semai.create') }}" class="btn btn-emerald btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Semai Baru
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
