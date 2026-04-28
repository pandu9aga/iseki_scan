@extends('layouts.user')

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .badge-day-1 { background-color: #28a745; color: #fff; }
    .badge-day-2 { background-color: #17a2b8; color: #fff; }
    .badge-day-3 { background-color: #ffc107; color: #fff; }
    .badge-day-4 { background-color: #dc3545; color: #fff; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-clipboard-check mr-2"></i>Check List</h1>

    {{-- Filter --}}
    <div class="card shadow-sm mb-3 border-left-primary">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('user.check') }}" class="form-inline d-flex align-items-center flex-wrap">
                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center">
                    <label class="mr-2 font-weight-bold text-gray-700" style="font-size:0.85rem;">
                        <i class="fas fa-calendar mr-1"></i>Tanggal Input:
                    </label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                </div>
                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center">
                    <label class="mr-2 font-weight-bold text-success" style="font-size:0.85rem;">
                        <i class="fas fa-bullseye mr-1"></i>Target Check:
                    </label>
                    <input type="date" name="target_date" class="form-control form-control-sm border-success" value="{{ request('target_date') }}">
                </div>
                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center">
                    <label class="mr-2 text-gray-700" style="font-size:0.8rem;">Status:</label>
                    <select name="status" class="form-control form-control-sm" style="min-width: 100px;">
                        <option value="">Semua</option>
                        <option value="today" {{ request('status') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Besok</option>
                        <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>2 Hari Lagi</option>
                        <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>3 Hari Lagi</option>
                        <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>4 Hari Lagi</option>
                    </select>
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
                    <a href="{{ route('user.check', ['target_date' => \Carbon\Carbon::today()->format('Y-m-d')]) }}" class="btn btn-danger btn-sm mr-1"><i class="fas fa-calendar-day"></i> Cek Hari Ini</a>
                    <a href="{{ route('user.check') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sync-alt"></i> Reset</a>
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
                            <td>{{ $c->rack_name }}</td>
                            <td>
                                @php
                                    // Status_Check sekarang menyimpan target date (misal 2026-04-26)
                                    $targetDate = \Carbon\Carbon::parse($c->Status_Check)->startOfDay();
                                    $timeCheckDate = \Carbon\Carbon::parse($c->Time_Check)->startOfDay();
                                    $today = \Carbon\Carbon::today();
                                    
                                    // Hitung hari status (DATEDIFF)
                                    $daysDiff = $timeCheckDate->diffInDays($targetDate);
                                    
                                    if ($targetDate->equalTo($today)) {
                                        $badgeText = "Hari Ini";
                                        $badgeClass = "badge-danger"; 
                                    } else {
                                        $badgeText = $targetDate->format('d M Y');
                                        if ($daysDiff == 1) $badgeClass = "badge-day-1";
                                        elseif ($daysDiff == 2) $badgeClass = "badge-day-2";
                                        elseif ($daysDiff == 3) $badgeClass = "badge-day-3";
                                        else $badgeClass = "badge-day-4";
                                        
                                        if ($targetDate->lessThan($today)) {
                                            $badgeClass = "badge-secondary"; 
                                        }
                                    }
                                @endphp
                                <span class="badge {{ $badgeClass }} px-2 py-1">{{ $badgeText }}</span>
                            </td>
                            <td>{{ $c->checker_name }}</td>
                            <td class="text-center">
                                @php
                                    $filterParams = array_filter(request()->only(['date', 'target_date', 'status', 'checker']));
                                    $reqParams = array_merge(['check_id' => $c->Id_Checks, 'code_rack' => $c->Code_Rack, 'code_item' => $c->Code_Item_Rack], $filterParams);
                                @endphp
                                <a href="{{ route('request', $reqParams) }}" class="btn btn-sm btn-info mb-1" title="Request Ulang">
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
$(document).ready(function() {
    $('#checkTable').DataTable({
        pageLength: 100,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [],
        language: {
            search: "Cari:", lengthMenu: "Tampilkan _MENU_ data",
            info: "Data _START_-_END_ dari _TOTAL_", infoEmpty: "Tidak ada data",
            zeroRecords: "Tidak ditemukan",
            emptyTable: '<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Belum ada data check.</div>',
            paginate: { next: "Selanjutnya", previous: "Sebelumnya" }
        }
    });
});
</script>
@endsection
