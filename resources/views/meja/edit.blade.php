@extends('layouts.app')
@section('title','Edit Meja')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-warning"><h5 class="mb-0">✏️ Edit Meja</h5></div>
            <div class="card-body">
                <form action="{{ route('meja.update',$meja->id_meja) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Meja</label>
                        <input type="text" name="meja" class="form-control" value="{{ old('meja', $meja->meja) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah Lubang</label>
                        <input type="number" name="jumlah_lubang" class="form-control" value="{{ old('jumlah_lubang', $meja->jumlah_lubang) }}" required min="1">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button>
                        <a href="{{ route('meja.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
