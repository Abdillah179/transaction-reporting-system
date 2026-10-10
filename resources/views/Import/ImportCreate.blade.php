@extends('Layouts.App')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Upload CSV</h1>
                <a href="/Import" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form id="upload-form" data-upload-id="{{ $uploadId }}" data-chunk-size="{{ $chunkSize }}">
                        @csrf

                        <div class="mb-3">
                            <label for="file" class="form-label">File CSV</label>
                            <input type="file" id="file" name="file" accept=".csv" class="form-control"
                                required>
                            <div class="form-text">Maksimal 1 GB. File akan dikirim bertahap dan diproses di background.
                            </div>
                        </div>

                        <div id="upload-progress" class="mb-3 d-none">
                            <div class="progress mb-1" style="height: 20px">
                                <div id="upload-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                    style="width: 0%"></div>
                            </div>
                            <small id="upload-text" class="text-muted"></small>
                        </div>

                        <div id="upload-error" class="alert alert-danger d-none"></div>

                        <button type="submit" id="upload-button" class="btn btn-primary">Upload & Import</button>
                        <a href="/Import" class="btn btn-outline-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/import-create.js') }}?v={{ filemtime(public_path('js/import-create.js')) }}"></script>
@endpush
