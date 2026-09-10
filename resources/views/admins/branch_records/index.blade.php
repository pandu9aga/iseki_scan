@extends('layouts.main')

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-qrcode text-info mr-2"></i>Branch Record List</h1>
        <a href="{{ route('admin.branch_record.export', ['date' => $date, 'month' => $month, 'member' => $selectedMember]) }}" class="btn btn-sm btn-success shadow-sm">
            <i class="fas fa-download fa-sm text-white-50 mr-1"></i> Export Excel
        </a>
    </div>

    {{-- Ringkasan Record Member sesuai filter tanggal/bulan (seperti halaman check) --}}
    <div class="card shadow-sm mb-3 border-left-info">
        <div class="card-header py-3 bg-info text-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-users mr-2"></i>Ringkasan Record Member {{ $isMonthly ? 'Bulan Ini' : 'Hari Ini' }} ({{ $summaryLabel }})</h6>
            <span class="badge badge-light font-weight-bold px-3 py-2 text-info"><i class="fas fa-database mr-1"></i>Total Scan Rak: {{ $summaryTotal }}</span>
        </div>
        <div class="card-body py-3">
            @if(count($checkerSummary) > 0)
            <div class="row">
                @foreach($checkerSummary as $name => $count)
                <div class="col-6 col-md-3 col-lg-2 mb-2">
                    <div class="border rounded p-2 text-center bg-light" style="height:100%;">
                        <div class="h4 font-weight-bold text-info mb-0">{{ $count }}</div>
                        <div class="small text-muted text-truncate" title="{{ $name }}">{{ $name }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-muted text-center mb-0"><i class="fas fa-info-circle mr-1"></i>Belum ada branch record pada periode ini.</p>
            @endif
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card shadow-sm mb-3 border-left-info">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('admin.branch_record') }}" id="filterBranchForm" class="form-inline d-flex align-items-center flex-wrap">
                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center flex-wrap" style="gap:6px;">
                    <div class="btn-group btn-group-sm" role="group" style="margin-right:6px;">
                        <button type="button" class="btn {{ request('month') ? 'btn-outline-info' : 'btn-info' }}" onclick="setFilterMode('harian')">Harian</button>
                        <button type="button" class="btn {{ request('month') ? 'btn-info' : 'btn-outline-info' }}" onclick="setFilterMode('bulanan')">Bulanan</button>
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
                    <label class="mr-2 text-gray-700" style="font-size:0.8rem;">Member:</label>
                    <select name="member" class="form-control form-control-sm" style="min-width: 140px;" onchange="this.form.submit()">
                        <option value="">-- Semua Member --</option>
                        @foreach($memberList as $id => $name)
                        <option value="{{ $id }}" {{ request('member') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ml-auto d-flex align-items-center" style="gap:4px;">
                    <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
                    <a href="{{ route('admin.branch_record') }}" class="btn btn-secondary btn-sm" title="Reset Filter"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-table mr-1"></i>Data Branch Record</h6>
            <span class="badge badge-info">{{ $records->count() }} Data Tampil</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="branchRecordTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 40px;" class="text-center">No</th>
                            <th style="width: 110px;">Tanggal</th>
                            <th style="width: 90px;" class="text-center">Waktu</th>
                            <th>Kode Rak</th>
                            <th>Member / Operator</th>
                            <th style="width: 140px;">Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $r)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $r->Day_Branch_Record }}</td>
                            <td class="text-center">{{ $r->Time_Branch_Record ? substr($r->Time_Branch_Record, 0, 5) : '-' }}</td>
                            <td class="font-weight-bold text-info">{{ $r->Code_Rack }}</td>
                            <td>{{ $r->display_name ?? ('Member #' . $r->Id_User) }}</td>
                            <td>{{ $r->Updated_At_Branch_Record ?? '-' }}</td>
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
        $('#branchRecordTable').DataTable({
            "pageLength": 25,
            "ordering": true
        });
    });

    function setFilterMode(mode) {
        var form = document.getElementById('filterBranchForm');
        var groupHarian = document.getElementById('filterHarianGroup');
        var groupBulanan = document.getElementById('filterBulananGroup');
        var dateInput = document.getElementById('filterDate');
        var monthInput = document.getElementById('filterMonth');

        if (mode === 'bulanan') {
            dateInput.disabled = true;
            monthInput.disabled = false;
            groupHarian.style.display = 'none';
            groupBulanan.style.display = 'inline-flex';
            if (!monthInput.value) {
                var today = new Date();
                monthInput.value = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
            }
        } else {
            monthInput.disabled = true;
            dateInput.disabled = false;
            groupBulanan.style.display = 'none';
            groupHarian.style.display = 'inline-flex';
        }
        form.submit();
    }

    function moveFilterDate(days) {
        var input = document.getElementById('filterDate');
        var cur = new Date(input.value + 'T00:00:00');
        if (isNaN(cur.getTime())) cur = new Date();
        cur.setDate(cur.getDate() + days);
        input.value = cur.getFullYear() + '-' + String(cur.getMonth() + 1).padStart(2, '0') + '-' + String(cur.getDate()).padStart(2, '0');
        document.getElementById('filterBranchForm').submit();
    }

    function moveFilterMonth(months) {
        var input = document.getElementById('filterMonth');
        var val = input.value || (new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0'));
        var parts = val.split('-');
        var cur = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, 1);
        cur.setMonth(cur.getMonth() + months);
        input.value = cur.getFullYear() + '-' + String(cur.getMonth() + 1).padStart(2, '0');
        document.getElementById('filterBranchForm').submit();
    }
</script>
@endsection