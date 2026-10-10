@extends('Layouts.App')

@section('content')
    @php
        $badge = ['pending' => 'secondary', 'processing' => 'primary', 'completed' => 'success', 'failed' => 'danger'];
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Detail Import</h1>
            <div class="text-muted small">{{ $detailData->file_name }} &middot; diupload oleh
                {{ $detailData->user->name ?? '-' }}</div>
        </div>
        <a href="/Import" class="btn btn-outline-secondary btn-sm">Kembali</a>
    </div>

    <div class="card mb-4" id="import-progress" data-progress-url="/Import/progress/{{ $detailData->id }}"
        data-initial="{{ json_encode($progress) }}">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span id="status-badge"
                    class="badge text-bg-{{ $badge[$progress['status']] ?? 'secondary' }}">{{ ucfirst($progress['status']) }}</span>
                <span id="percent-text" class="fw-semibold">{{ number_format($progress['percent'], 1, ',', '.') }}%</span>
            </div>

            <div class="progress mb-3" style="height: 20px">
                <div id="progress-bar"
                    class="progress-bar {{ in_array($progress['status'], ['pending', 'processing']) ? 'progress-bar-striped progress-bar-animated' : '' }}"
                    style="width: {{ $progress['percent'] }}%"></div>
            </div>

            <div class="row g-3 text-center">
                <div class="col-6 col-md-3">
                    <div class="text-muted small">Diproses</div>
                    <div class="fw-semibold">
                        <span id="processed-rows">{{ number_format($progress['processed_rows'], 0, ',', '.') }}</span>
                        / <span id="total-rows">{{ number_format($progress['total_rows'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small">Gagal</div>
                    <div id="failed-rows" class="fw-semibold text-danger">
                        {{ number_format($progress['failed_rows'], 0, ',', '.') }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small">Kecepatan</div>
                    <div class="fw-semibold"><span
                            id="speed">{{ number_format($progress['rows_per_second'], 0, ',', '.') }}</span>
                        baris/detik</div>
                </div>
                <div class="col-6 col-md-3">
                    <div id="time-label" class="text-muted small">
                        {{ $progress['status'] === 'processing' ? 'Estimasi Selesai' : 'Durasi' }}</div>
                    <div id="time-value" class="fw-semibold">-</div>
                </div>
            </div>

            <div id="reading-info"
                class="alert alert-info small mt-3 mb-0 {{ $progress['status'] === 'processing' && $progress['total_rows'] === 0 ? '' : 'd-none' }}">
                Sedang membaca file dan membagi data menjadi beberapa bagian...
            </div>

            @if ($progress['error_message'])
                <div class="alert alert-danger small mt-3 mb-0">{{ $progress['error_message'] }}</div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">Baris yang Gagal Diimport</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0 small">
                    <thead>
                        <tr>
                            <th style="width: 100px">Baris ke-</th>
                            <th>Alasan</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dataError as $error)
                            <tr>
                                <td>{{ number_format($error->row_number, 0, ',', '.') }}</td>
                                <td class="text-danger">{{ $error->reason }}</td>
                                <td><code class="text-body">{{ $error->raw_data }}</code></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Tidak ada baris yang gagal.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($dataError->hasPages())
            <div class="card-footer bg-white">
                {{ $dataError->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/import-detail.js') }}?v={{ filemtime(public_path('js/import-detail.js')) }}"></script>
@endpush
