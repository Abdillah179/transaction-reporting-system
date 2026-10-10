@extends('Layouts.App')

@section('content')
    <div id="dashboard-data" class="d-none"
        data-daily="{{ json_encode($daily) }}"
        data-monthly="{{ json_encode($monthly) }}"
        data-categories="{{ json_encode($perCategory) }}"></div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Dashboard</h1>

        <form method="GET" action="/Dashboard" class="d-flex flex-wrap align-items-center gap-2">
            <input type="date" name="start_date" value="{{ request('start_date') }}"
                class="form-control form-control-sm w-auto">
            <span class="text-muted small">s/d</span>
            <input type="date" name="end_date" value="{{ request('end_date') }}"
                class="form-control form-control-sm w-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="/Dashboard" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if ($totalTransactions === 0)
        <div class="alert alert-info">Belum ada data transaksi. Silakan import file CSV terlebih dahulu.</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Transaksi</div>
                    <div class="fs-4 fw-semibold">{{ number_format($totalTransactions, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Amount (Berhasil)</div>
                    <div class="fs-4 fw-semibold">Rp {{ number_format($totalAmount, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Rata-rata per Transaksi</div>
                    <div class="fs-4 fw-semibold">Rp {{ number_format($averageAmount, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Success Rate</div>
                    <div class="fs-4 fw-semibold">{{ number_format($successRate, 1, ',', '.') }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white">Jumlah Transaksi per Hari</div>
                <div class="card-body">
                    <div style="height: 300px">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white">Amount per Kategori</div>
                <div class="card-body">
                    <div style="height: 300px">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white">Total Amount per Bulan</div>
                <div class="card-body">
                    <div style="height: 300px">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white">Transaksi per Status</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-end">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $badge = ['success' => 'success', 'pending' => 'warning', 'failed' => 'danger'];
                            @endphp
                            @forelse ($perStatus as $item)
                                <tr>
                                    <td><span
                                            class="badge text-bg-{{ $badge[$item->status->code] ?? 'secondary' }}">{{ $item->status->name }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format($item->total_count, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        {{ number_format(($item->total_count / max($totalTransactions, 1)) * 100, 1, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada data</td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">Ringkasan per Kategori</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th class="text-end">Transaksi Berhasil</th>
                        <th class="text-end">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($perCategory as $item)
                        <tr>
                            <td>{{ $item->category->name }}</td>
                            <td class="text-end">{{ number_format($item->total_count, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">Belum ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}"></script>
@endpush
