@extends('layouts.app')
@section('title','Edit Tanaman')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-warning"><h5 class="mb-0">✏️ Edit Tanaman</h5></div>
            <div class="card-body">
                <form action="{{ route('tanaman.update',$tanaman->id_tanaman) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Tanaman</label>
                        <input type="text" name="nama_tanaman" class="form-control" value="{{ old('nama_tanaman',$tanaman->nama_tanaman) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Meja</label>
                        <select name="id_meja" class="form-select">
                            <option value="">-- Tidak ada --</option>
                            @foreach($mejas as $m)
                            <option value="{{ $m->id_meja }}" {{ $tanaman->id_meja == $m->id_meja ? 'selected' : '' }}>{{ $m->meja }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button>
                        <a href="{{ route('tanaman.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
