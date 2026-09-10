@extends('layouts.app')
@section('title','Edit Penerima Notif')
@section('content')
<div class="row justify-content-center"><div class="col-md-5">
    <div class="card shadow-sm">
        <div class="card-header bg-warning"><h5 class="mb-0">✏️ Edit Penerima Notifikasi</h5></div>
        <div class="card-body">
            <form action="{{ route('penerima_notif.update',$data->id_penerima_notif) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label fw-bold">Nama</label><input type="text" name="nama" class="form-control" value="{{ $data->nama }}" required></div>
                <div class="mb-3"><label class="form-label fw-bold">No HP WhatsApp</label>
                    <div class="input-group"><span class="input-group-text"><i class="fab fa-whatsapp"></i></span><input type="text" name="no_hp" class="form-control" value="{{ $data->no_hp }}" required></div></div>
                <div class="d-flex gap-2"><button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Update</button><a href="{{ route('penerima_notif.index') }}" class="btn btn-secondary">Batal</a></div>
            </form>
        </div>
    </div>
</div></div>
@endsection
