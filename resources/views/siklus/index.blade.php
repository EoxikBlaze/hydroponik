@extends('layouts.app')
@section('title','Data Siklus')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>🔄 Data Siklus Tanam</h3>
    <a href="{{ route('siklus.create') }}" class="btn btn-success"><i class="fas fa-plus me-1"></i>Tambah Siklus</a>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-success"><tr><th>#</th><th>Tanaman</th><th>Nama Siklus</th><th>Waktu (Hari)</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($sikluses as $s)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $s->tanaman?->nama_tanaman ?? '-' }}</td>
                    <td><strong>{{ $s->nama_siklus }}</strong></td>
                    <td>{{ $s->waktu_siklus }} hari</td>
                    <td>
                        <a href="{{ route('siklus.edit',$s->id_siklus) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('siklus.destroy',$s->id_siklus) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button></form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data siklus.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
