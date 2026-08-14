@extends('layouts.main')

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .badge-day-1 {
        background-color: #28a745;
        color: #fff;
    }

    .badge-day-2 {
        background-color: #17a2b8;
        color: #fff;
    }

    .badge-day-3 {
        background-color: #ffc107;
        color: #fff;
    }

    .badge-day-4 {
        background-color: #dc3545;
        color: #fff;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-clipboard-check mr-2"></i>Check List</h1>

    {{-- Ringkasan Checker Hari Ini (khusus halaman admin) --}}
    <div class="card shadow-sm mb-3 border-left-primary">
        <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-users mr-2"></i>Checker Hari Ini ({{ \Carbon\Carbon::today()->format('d M Y') }})</h6>
            <span class="badge badge-light font-weight-bold px-3 py-2"><i class="fas fa-qrcode mr-1"></i>Total Scan: {{ $todayTotal }}</span>
        </div>
        <div class="card-body py-3">
            @if(count($checkerSummary) > 0)
                <div class="row">
                    @foreach($checkerSummary as $name => $count)
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <div class="border rounded p-2 text-center bg-light" style="height:100%;">
                            <div class="h4 font-weight-bold text-primary mb-0">{{ $count }}</div>
                            <div class="small text-muted text-truncate" title="{{ $name }}">{{ $name }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted text-center mb-0"><i class="fas fa-info-circle mr-1"></i>Belum ada check hari ini.</p>
            @endif
        </div>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm mb-3 border-left-primary">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('admin.check') }}" id="filterCheckForm" class="form-inline d-flex align-items-center flex-wrap">
                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center flex-wrap" style="gap:6px;">
                    <div class="btn-group btn-group-sm" role="group" style="margin-right:6px;">
                        <button type="button" class="btn {{ request('month') ? 'btn-outline-primary' : 'btn-primary' }}" onclick="setFilterMode('harian')">Harian</button>
                        <button type="button" class="btn {{ request('month') ? 'btn-primary' : 'btn-outline-primary' }}" onclick="setFilterMode('bulanan')">Bulanan</button>
                    </div>

                    <span id="filterHarianGroup" class="d-inline-flex align-items-center" style="gap:4px;{{ request('month') ? 'display:none;' : '' }}">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveFilterDate(-1)" title="Sebelumnya"><i class="fas fa-chevron-left"></i></button>
                        <input type="date" name="date" id="filterDate" class="form-control form-control-sm" value="{{ request('date', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveFilterDate(1)" title="Selanjutnya"><i class="fas fa-chevron-right"></i></button>
                    </span>

                    <span id="filterBulananGroup" class="d-inline-flex align-items-center" style="gap:4px;{{ request('month') ? '' : 'display:none;' }}">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveFilterMonth(-1)" title="Bulan Sebelumnya"><i class="fas fa-chevron-left"></i></button>
                        <input type="month" name="month" id="filterMonth" class="form-control form-control-sm" value="{{ request('month') }}">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveFilterMonth(1)" title="Bulan Selanjutnya"><i class="fas fa-chevron-right"></i></button>
                    </span>
                </div>
                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center">
                    <label class="mr-2 text-gray-700" style="font-size:0.8rem;">Checker:</label>
                    <select name="checker" class="form-control form-control-sm" style="min-width: 140px;">
                        <option value="">Semua</option>
                        @foreach($checkerList as $id => $name)
                        <option value="{{ $id }}" {{ request('checker') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-2 mt-md-0">
                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search"></i> Terapkan</button>
                    <button type="button" class="btn btn-danger btn-sm mr-1" onclick="setFilterToday()"><i class="fas fa-calendar-day"></i> Hari Ini</button>
                    <a href="{{ route('admin.check') }}" class="btn btn-outline-secondary btn-sm mr-1"><i class="fas fa-sync-alt"></i> Reset</a>
                    <a href="{{ route('admin.check.export', request()->only(['date', 'month', 'checker'])) }}" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Export Excel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    {{-- Table --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="checkTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Code Rack</th>
                            <th>Code Item</th>
                            <th>Area Check</th>
                            <th>Rack Name</th>
                            <th>Status</th>
                            <th>Checker</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($checks as $index => $c)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $c->Time_Check ? \Carbon\Carbon::parse($c->Time_Check)->format('d/m/y H:i') : '-' }}</td>
                            <td><code>{{ $c->Code_Rack }}</code></td>
                            <td>{{ $c->Code_Item_Rack }}</td>
                            <td>{{ $c->Area_Check ?? '-' }}</td>
                            <td>{{ $c->rack_name }}</td>
                            <td>
                                @php
                                    $timeCheckDate = \Carbon\Carbon::parse($c->Time_Check)->startOfDay();
                                    $today = \Carbon\Carbon::today();
                                @endphp
                                @if(is_null($c->Status_Check))
                                    @if($c->Auto_Check == 1)
                                        <span class="badge badge-info px-2 py-1">Scan</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1">Selesai</span>
                                    @endif
                                @else
                                    @php
                                        // Status_Check sekarang menyimpan target date (misal 2026-04-26)
                                        $targetDate = \Carbon\Carbon::parse($c->Status_Check)->startOfDay();
                                        // Hitung hari status (DATEDIFF)
                                        $daysDiff = $timeCheckDate->diffInDays($targetDate);
                                    @endphp
                                    @if ($targetDate->equalTo($today))
                                        <span class="badge badge-danger px-2 py-1">Hari Ini</span>
                                    @else
                                        @php
                                            $badgeText = $targetDate->format('d M Y');
                                            if ($daysDiff == 1) $badgeClass = "badge-day-1";
                                            elseif ($daysDiff == 2) $badgeClass = "badge-day-2";
                                            elseif ($daysDiff == 3) $badgeClass = "badge-day-3";
                                            else $badgeClass = "badge-day-4";

                                            if ($targetDate->lessThan($today)) {
                                                $badgeClass = "badge-secondary";
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }} px-2 py-1">{{ $badgeText }}</span>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $c->checker_name }}</td>
                            <td class="text-center">
                                @php
                                    $filterParams = array_filter(request()->only(['date', 'month', 'checker']));
                                    $reqParams = array_merge(['check_id' => $c->Id_Checks, 'code_rack' => $c->Code_Rack, 'code_item' => $c->Code_Item_Rack], $filterParams);
                                @endphp
                                <a href="{{ route('admin.requesting', $reqParams) }}" class="btn btn-sm btn-info mb-1" title="Request Ulang">
                                    <i class="fas fa-paper-plane"></i> Req
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
    function pad2(n) { return String(n).padStart(2, '0'); }
    function fmtDate(d) { return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()); }

    function setFilterMode(mode) {
        var harian = document.getElementById('filterHarianGroup');
        var bulanan = document.getElementById('filterBulananGroup');
        var dateInput = document.getElementById('filterDate');
        var monthInput = document.getElementById('filterMonth');
        if (mode === 'bulanan') {
            if (!monthInput.value) {
                var now = new Date();
                monthInput.value = now.getFullYear() + '-' + pad2(now.getMonth() + 1);
            }
            harian.style.display = 'none';
            bulanan.style.display = '';
            dateInput.disabled = true;
            monthInput.disabled = false;
        } else {
            harian.style.display = '';
            bulanan.style.display = 'none';
            dateInput.disabled = false;
            monthInput.disabled = true;
        }
        document.getElementById('filterCheckForm').submit();
    }

    function moveFilterDate(offset) {
        var d = new Date(document.getElementById('filterDate').value + 'T00:00:00');
        d.setDate(d.getDate() + offset);
        document.getElementById('filterDate').value = fmtDate(d);
        document.getElementById('filterCheckForm').submit();
    }

    function moveFilterMonth(offset) {
        var val = document.getElementById('filterMonth').value;
        var now = new Date();
        var y, m;
        if (val) {
            var p = val.split('-');
            y = parseInt(p[0], 10); m = parseInt(p[1], 10);
        } else {
            y = now.getFullYear(); m = now.getMonth() + 1;
        }
        var total = (y * 12) + (m - 1) + offset;
        y = Math.floor(total / 12);
        m = (total % 12) + 1;
        document.getElementById('filterMonth').value = y + '-' + pad2(m);
        document.getElementById('filterCheckForm').submit();
    }

    function setFilterToday() {
        var now = new Date();
        document.getElementById('filterDate').value = fmtDate(now);
        document.getElementById('filterDate').disabled = false;
        document.getElementById('filterMonth').disabled = true;
        document.getElementById('filterCheckForm').submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        var isBulanan = document.getElementById('filterMonth').value ? true : false;
        document.getElementById('filterDate').disabled = isBulanan;
        document.getElementById('filterMonth').disabled = !isBulanan;
    });

    $(document).ready(function() {
        $('#checkTable').DataTable({
            pageLength: 100,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            order: [],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Data _START_-_END_ dari _TOTAL_",
                infoEmpty: "Tidak ada data",
                zeroRecords: "Tidak ditemukan",
                emptyTable: '<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Belum ada data check.</div>',
                paginate: {
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            }
        });
    });
</script>
@endsection