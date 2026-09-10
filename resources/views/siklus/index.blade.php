@extends('layouts.app')
@section('title','Data Siklus Tanam')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-arrows-rotate me-2 text-primary"></i>Master Siklus Tanam</h4>
        <p class="text-muted small mb-0">Atur durasi dan target waktu perputaran siklus tanaman hidroponik</p>
    </div>
    <a href="{{ route('siklus.create') }}" class="btn btn-emerald px-3 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm">
        <i class="fas fa-plus"></i>
        <span>Tambah Siklus</span>
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-3" style="width: 60px;">No</th>
                    <th>Komoditas Tanaman</th>
                    <th>Nama Siklus</th>
                    <th>Estimasi Waktu</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sikluses as $s)
                <tr>
                    <td class="ps-3 fw-bold text-muted">{{ $loop->iteration }}</td>
                    <td>
                        <span class="badge badge-soft-success px-2 py-1 fs-6">
                            {{ $s->tanaman?->nama_tanaman ?? '-' }}
                        </span>
                    </td>
                    <td><strong class="text-dark">{{ $s->nama_siklus }}</strong></td>
                    <td>
                        <span class="badge bg-light text-dark border px-2 py-1">
                            <i class="fas fa-clock me-1 text-muted"></i>{{ $s->waktu_siklus }} Hari
                        </span>
                    </td>
                    <td class="text-end pe-3">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('siklus.edit', $s->id_siklus) }}" class="btn btn-sm btn-light border text-warning" title="Edit">
                                <i class="fas fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('siklus.destroy', $s->id_siklus) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data siklus ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Belum ada data siklus tanam.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
