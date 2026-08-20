@extends('layouts.main')

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container-fluid">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    @endif

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Rangkuman Selisih Sum Request vs Record</h1>
    </div>

    <!-- Info -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        Riwayat selisih per item antara <b>Sum Request</b> (jumlah diminta) dan <b>Sum Record</b> (jumlah yang datang/di-record).
        Ditampilkan berdasarkan <b>tanggal record</b>. Gunakan <b>Range Minus/Surplus</b> untuk menyaring, contoh <b>Minus 15</b> = data kurang dengan selisih -1 s/d -15, <b>Surplus 50</b> = data lebih dengan selisih +1 s/d +50.
        Selisih <b class="text-success">+</b> = datang lebih banyak, selisih <b class="text-danger">−</b> = datang lebih sedikit.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-4 border-left-primary">
        <div class="card-body py-3">
            <form action="{{ route('admin.sumhistory') }}" method="GET" id="filterForm">
                <div class="d-flex flex-wrap align-items-center" style="gap:12px;">

                    <!-- Filter Group -->
                    <div class="d-flex align-items-center">
                        <label class="mb-0 mr-2 text-gray-700 font-weight-bold" style="font-size: 1rem;" id="filter_label">{{ $month ? 'Month:' : 'Date:' }}</label>
                        <div class="input-group input-group-sm" style="width: auto; flex-wrap: nowrap;">
                            <div class="input-group-prepend">
                                <button class="btn" style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: white;" type="button" onclick="changeValue(-1)">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </div>

                            <!-- Date Input (Visible if not month mode) -->
                            <input type="date" name="date" class="form-control text-center font-weight-bold text-primary" id="filter_date" value="{{ $today }}" onchange="submitForm()" style="width: 150px; border-color: #f5c6cb; border-left: none; border-right: none; display: {{ $month ? 'none' : 'block' }};" {{ $month ? 'disabled' : '' }}>

                            <!-- Month Input (Visible if month mode) -->
                            <input type="month" name="month" class="form-control text-center font-weight-bold text-primary" id="filter_month" value="{{ $month }}" onchange="submitForm()" style="width: 150px; border-color: #f5c6cb; border-left: none; border-right: none; display: {{ $month ? 'block' : 'none' }};" {{ $month ? '' : 'disabled' }}>

                            <div class="input-group-append">
                                <button class="btn bg-white" style="border: 1px solid #f5c6cb; color: #f5c6cb;" type="button" onclick="changeValue(1)">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Button -->
                    <div class="d-flex align-items-center ml-2">
                        <button type="button" class="btn btn-sm bg-white font-weight-bold px-3" onclick="toggleMode()" id="toggleModeBtn" style="border: 2px solid #b7b9cc; color: #4e73df; border-radius: 6px;">{{ $month ? 'Date' : 'Month' }}</button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <!-- Selisih ±10 -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Selisih ≥ 10</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['s10']['total'] }} Item</div>
                            <div class="small mt-1">
                                <span class="text-success"><i class="fas fa-arrow-up"></i> Lebih: {{ $stats['s10']['lebih'] }}</span>
                                &nbsp;|&nbsp;
                                <span class="text-danger"><i class="fas fa-arrow-down"></i> Kurang: {{ $stats['s10']['kurang'] }}</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selisih ±25 -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Selisih ≥ 25</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['s25']['total'] }} Item</div>
                            <div class="small mt-1">
                                <span class="text-success"><i class="fas fa-arrow-up"></i> Lebih: {{ $stats['s25']['lebih'] }}</span>
                                &nbsp;|&nbsp;
                                <span class="text-danger"><i class="fas fa-arrow-down"></i> Kurang: {{ $stats['s25']['kurang'] }}</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selisih ≥ 50 -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Selisih ≥ 50</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['s50']['total'] }} Item</div>
                            <div class="small mt-1">
                                <span class="text-success"><i class="fas fa-arrow-up"></i> Lebih: {{ $stats['s50']['lebih'] }}</span>
                                &nbsp;|&nbsp;
                                <span class="text-danger"><i class="fas fa-arrow-down"></i> Kurang: {{ $stats['s50']['kurang'] }}</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Data Selisih</h6>
            <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                <label class="mb-0 mr-1 text-gray-700 small font-weight-bold">Minus:</label>
                <input type="number" class="form-control form-control-sm" id="filter_min" placeholder="" style="width: 100px;">
                <label class="mb-0 ml-2 mr-1 text-gray-700 small font-weight-bold">Surplus:</label>
                <input type="number" class="form-control form-control-sm" id="filter_max" placeholder="" style="width: 100px;">
                <button type="button" class="btn btn-sm btn-primary font-weight-bold" onclick="applyRange()">Terapkan</button>
                <a href="{{ route('sumhistory.export') }}" class="btn btn-sm btn-success font-weight-bold" id="exportBtn" onclick="return updateExportHref(event)">
                    <i class="fas fa-file-excel mr-1"></i>Export Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center" id="sumHistoryTable" width="100%" cellspacing="0" style="font-size: 13px;">
                    <thead class="bg-gradient-primary text-white">
                        <tr>
                            <th>No</th>
                            <th>Tgl Record</th>
                            <th>Tgl Request</th>
                            <th>Rack</th>
                            <th>Item Code</th>
                            <th>Nama Item</th>
                            <th>Sum Request</th>
                            <th>Sum Record</th>
                            <th>Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
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
    function changeValue(offset) {
        var isMonthMode = document.getElementById('filter_month').style.display !== 'none';
        if (isMonthMode) {
            var input = document.getElementById('filter_month');
            if (!input.value) return;
            var parts = input.value.split('-');
            var d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1 + offset, 1);
            input.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        } else {
            var input = document.getElementById('filter_date');
            if (!input.value) return;
            var d = new Date(input.value + 'T00:00:00');
            d.setDate(d.getDate() + offset);
            input.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }
        submitForm();
    }

    function toggleMode() {
        var isMonthMode = document.getElementById('filter_month').style.display !== 'none';
        if (isMonthMode) {
            // Switch to Date Mode
            document.getElementById('filter_month').value = '';
            var d = new Date();
            document.getElementById('filter_date').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        } else {
            // Switch to Month Mode
            document.getElementById('filter_date').value = '';
            var d = new Date();
            document.getElementById('filter_month').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        }
        submitForm();
    }

    function submitForm() {
        var isMonthMode = document.getElementById('filter_month').style.display !== 'none';
        if (isMonthMode) {
            document.getElementById('filter_date').disabled = true;
        } else {
            document.getElementById('filter_month').disabled = true;
        }
        document.getElementById('filterForm').submit();
    }

    function applyRange() {
        var table = $.fn.DataTable.isDataTable('#sumHistoryTable') ? $('#sumHistoryTable').DataTable() : null;
        if (table) {
            table.draw();
        }
    }

    function updateExportHref(event) {
        var url = "{{ route('sumhistory.export') }}";
        var date = document.getElementById('filter_date').value;
        var month = document.getElementById('filter_month').value;
        var minus = document.getElementById('filter_min').value;
        var surplus = document.getElementById('filter_max').value;
        var params = [];
        if (month) {
            params.push('month=' + encodeURIComponent(month));
        } else if (date) {
            params.push('date=' + encodeURIComponent(date));
        }
        if (minus !== '') {
            params.push('minus_selisih=' + encodeURIComponent(minus));
        }
        if (surplus !== '') {
            params.push('surplus_selisih=' + encodeURIComponent(surplus));
        }
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        event.target.href = url;
        return true;
    }

    $(document).ready(function() {
        var table = $('#sumHistoryTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 50,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            ajax: {
                url: "{{ route('sumhistory.data') }}",
                data: function(d) {
                    d.minus_selisih = $('#filter_min').val();
                    d.surplus_selisih = $('#filter_max').val();
                    d.date = $('#filter_date').val();
                    d.month = $('#filter_month').val();
                }
            },
            columns: [{
                    data: null,
                    name: 'no',
                    searchable: false,
                    orderable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'Day_Record',
                    name: 'rec.Day_Record',
                    render: function(data, type, row) {
                        if (!data) return '-';
                        return data + (row.Time_Record ? ' ' + row.Time_Record : '');
                    }
                },
                {
                    data: 'Day_Request',
                    name: 'req.Day_Request',
                    render: function(data, type, row) {
                        if (!data) return '-';
                        return data + (row.Time_Request ? ' ' + row.Time_Request : '');
                    }
                },
                {
                    data: 'Code_Rack',
                    name: 'req.Code_Rack'
                },
                {
                    data: 'Code_Item_Rack',
                    name: 'req.Code_Item_Rack'
                },
                {
                    data: 'Rack_Name',
                    name: 'Rack_Name',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'Sum_Request',
                    name: 'req.Sum_Request'
                },
                {
                    data: 'Sum_Record',
                    name: 'rec.Sum_Record'
                },
                {
                    data: 'Selisih',
                    name: 'Selisih',
                    searchable: false,
                    orderable: false,
                    render: function(data) {
                        if (data === null || data === undefined) return '-';
                        var cls = data > 0 ? 'text-success' : (data < 0 ? 'text-danger' : 'text-muted');
                        var icon = data > 0 ? '<i class="fas fa-arrow-up mr-1"></i>' : (data < 0 ? '<i class="fas fa-arrow-down mr-1"></i>' : '');
                        return '<span class="font-weight-bold ' + cls + '">' + icon + (data > 0 ? '+' : '') + data + '</span>';
                    }
                },
            ],
            order: [
                [1, 'desc']
            ],
        });

        $('#filter_min, #filter_max').on('keydown', function(e) {
            if (e.key === 'Enter') {
                applyRange();
            }
        });
    });
</script>
@endsection