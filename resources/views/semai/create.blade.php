@extends('layouts.app')
@section('title','Tambah Semai')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white"><h5 class="mb-0">🌿 Tambah Data Semai</h5></div>
            <div class="card-body">
                <form action="{{ route('semai.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanaman</label>
                            <select name="id_tanaman" class="form-select" required>
                                <option value="">-- Pilih Tanaman --</option>
                                @foreach($tanaman as $t)
                                <option value="{{ $t->id_tanaman }}" {{ old('id_tanaman') == $t->id_tanaman ? 'selected':'' }}>{{ $t->nama_tanaman }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Siklus</label>
                            <select name="id_siklus" class="form-select">
                                <option value="">-- Pilih Siklus --</option>
                                @foreach($siklus as $sk)
                                <option value="{{ $sk->id_siklus }}" {{ old('id_siklus') == $sk->id_siklus ? 'selected':'' }}>{{ $sk->nama_siklus }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Jumlah Benih</label>
                            <input type="number" name="jumlah_benih" class="form-control" value="{{ old('jumlah_benih') }}" min="1" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tgl Awal Semai</label>
                            <input type="date" name="tgl_awal_semai" class="form-control" value="{{ old('tgl_awal_semai') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tgl Selesai Semai</label>
                            <input type="date" name="tgl_akhir_semai" class="form-control" value="{{ old('tgl_akhir_semai') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Opsional...">{{ old('keterangan') }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Simpan</button>
                        <a href="{{ route('semai.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
