@extends('layouts.app')
@section('title', 'Data Meja Hidroponik')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-table-cells-large me-2 text-primary"></i>Master Data: Meja Tanam Hidroponik</h4>
        <p class="text-muted small mb-0">Kelola inventaris instalasi meja tanam, kapasitas lubang netpot, dan alokasi bibit</p>
    </div>
    <a href="{{ route('meja.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
        <i class="fas fa-plus me-1"></i> Tambah Meja Baru
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Nama Meja Tanam</th>
                        <th>Kapasitas Lubang Netpot</th>
                        <th>Peruntukan / Kategori</th>
                        <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mejas as $m)
                    <tr>
                        <td class="ps-4 fw-bold text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; color: #0284c7;">
                                    <i class="fas fa-border-all"></i>
                                </div>
                                <span class="fw-bold text-dark">{{ $m->meja }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-soft-success px-3 py-2 fs-6">
                                <i class="fas fa-circle-dot me-1"></i>{{ number_format($m->jumlah_lubang) }} Lubang
                            </span>
                        </td>
                        <td>
                            @if(str_contains(strtolower($m->meja), 'peremajaan'))
                                <span class="badge badge-soft-warning">Meja Peremajaan</span>
                            @elseif(str_contains(strtolower($m->meja), 'pendewasaan'))
                                <span class="badge badge-soft-primary">Meja Pendewasaan</span>
                            @else
                                <span class="badge badge-soft-info">Umum / Khusus</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('meja.edit', $m->id_meja) }}" class="btn btn-light border text-secondary" title="Edit">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('meja.destroy', $m->id_meja) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus meja tanam ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-light border text-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="fas fa-table-cells-large fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Meja Tanam</h6>
                            <p class="small text-muted mb-3">Tambahkan data instalasi meja tanam di greenhouse.</p>
                            <a href="{{ route('meja.create') }}" class="btn btn-emerald btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Meja Baru
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
