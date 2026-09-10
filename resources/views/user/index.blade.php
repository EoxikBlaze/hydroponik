@extends('layouts.app')
@section('title','Manajemen Pengguna')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-users-gear me-2 text-primary"></i>Manajemen Pengguna Sistem</h4>
        <p class="text-muted small mb-0">Kelola akun akses admin dan staf pengelola hidroponik</p>
    </div>
    <a href="{{ route('user.create') }}" class="btn btn-emerald px-3 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm">
        <i class="fas fa-plus"></i>
        <span>Tambah Pengguna</span>
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-3" style="width: 60px;">No</th>
                    <th>Nama Pengguna</th>
                    <th>Email</th>
                    <th>Hak Akses</th>
                    <th>Tanggal Terdaftar</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td class="ps-3 fw-bold text-muted">{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center fw-bold text-secondary border" style="width: 34px; height: 34px;">
                                {{ strtoupper(substr($u->username, 0, 1)) }}
                            </div>
                            <div>
                                <strong class="text-dark">{{ $u->username }}</strong>
                                @if($u->id == auth()->id())
                                    <span class="badge badge-soft-success ms-1">Akun Anda</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-muted">{{ $u->email }}</td>
                    <td>
                        <span class="badge {{ $u->level === 'admin' ? 'badge-soft-danger' : 'badge-soft-info' }} px-2 py-1">
                            {{ ucfirst($u->level) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $u->created_at?->format('d M Y') }}</td>
                    <td class="text-end pe-3">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('user.edit', $u->id) }}" class="btn btn-sm btn-light border text-warning" title="Edit">
                                <i class="fas fa-pen-to-square"></i>
                            </a>
                            @if($u->id != auth()->id())
                            <form action="{{ route('user.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pengguna ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada pengguna.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
