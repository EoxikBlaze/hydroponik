@extends('layouts.app')
@section('title','Manajemen User')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>👥 Manajemen User</h3>
    <a href="{{ route('user.create') }}" class="btn btn-success"><i class="fas fa-plus me-1"></i>Tambah User</a>
</div>
<div class="card shadow-sm"><div class="card-body p-0">
    <table class="table table-hover mb-0">
        <thead class="table-success"><tr><th>#</th><th>Username</th><th>Email</th><th>Level</th><th>Dibuat</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($users as $u)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $u->username }}</strong> @if($u->id == auth()->id())<span class="badge bg-info">Anda</span>@endif</td>
                <td>{{ $u->email }}</td>
                <td><span class="badge bg-{{ $u->level === 'admin' ? 'danger' : 'secondary' }}">{{ ucfirst($u->level) }}</span></td>
                <td>{{ $u->created_at?->format('d/m/Y') }}</td>
                <td>
                    <a href="{{ route('user.edit',$u->id) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                    @if($u->id != auth()->id())
                    <form action="{{ route('user.destroy',$u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada user.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
@endsection
