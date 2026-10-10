@extends('Layouts.App')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Tambah Status</h1>
                <a href="/Status" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="/postcreatestatus">
                        @csrf
                        @method('POST')

                        <div class="mb-3">
                            <label for="code" class="form-label">Kode</label>
                            <input type="text" id="code" name="code" value="{{ old('code') }}" maxlength="20"
                                class="form-control @error('code') is-invalid @enderror" required autofocus>
                            <div class="form-text">Huruf kecil tanpa spasi.</div>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="30"
                                class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="/Status" class="btn btn-outline-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
