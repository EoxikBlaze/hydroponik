@extends('layouts.app')
@section('title', 'Daftar Varietas Tanaman')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-carrot me-2 text-success"></i>Master Data: Varietas Tanaman</h4>
        <p class="text-muted small mb-0">Kelola jenis sayuran hidroponik yang dibudidayakan di greenhouse HarvestHouse</p>
    </div>
    <a href="{{ route('tanaman.create') }}" class="btn btn-emerald btn-sm px-3 py-2">
        <i class="fas fa-plus me-1"></i> Tambah Varietas Baru
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th>Nama Tanaman</th>
                        <th>Meja Terhubung</th>
                        <th>Siklus Terdaftar</th>
                        <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tanaman as $t)
                    <tr>
                        <td class="ps-4 fw-bold text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; color: #059669;">
                                    <i class="fas fa-seedling"></i>
                                </div>
                                <span class="fw-bold text-dark">{{ $t->nama_tanaman }}</span>
                            </div>
                        </td>
                        <td>
                            @if($t->meja)
                                <span class="badge badge-soft-primary"><i class="fas fa-table-cells-large me-1"></i>{{ $t->meja->meja }}</span>
                            @else
                                <span class="text-muted small">Semua Meja</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-soft-info">{{ $t->siklus?->count() ?? 0 }} Tahap Siklus</span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('tanaman.edit', $t->id_tanaman) }}" class="btn btn-light border text-secondary" title="Edit">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('tanaman.destroy', $t->id_tanaman) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus varietas tanaman ini?')">
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
                            <i class="fas fa-carrot fa-3x mb-3 d-block text-muted opacity-50"></i>
                            <h6 class="fw-bold mb-1">Belum Ada Data Tanaman</h6>
                            <p class="small text-muted mb-3">Daftarkan jenis tanaman seperti Selada, Seledri, atau Pakcoy.</p>
                            <a href="{{ route('tanaman.create') }}" class="btn btn-emerald btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Tanaman
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
