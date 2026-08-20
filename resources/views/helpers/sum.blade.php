@extends($layout)
@section('style')
<link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
<style>
    .name-part-col {
        min-width: 90px;
        max-width: 110px;
        width: 100px;
        white-space: normal !important;
        word-break: break-word;
        font-size: 11px;
        line-height: 1.3;
    }
</style>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Sum (Part Sum Not Match)</h1>
    </div>

    <!-- Info -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        Daftar request yang barangnya datang tidak sesuai jumlah (kurang dari yang diminta, selisih >= 5).
        Status <b>OPEN</b> = kesalahan MC (menunggu MC melengkapi sisa barang via tombol "Selesai Kirim").
        Status <b>CLOSED</b> = MC sudah melengkapi sisa barang, Part Sum Not Match selesai.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <!-- Filter -->
    <div class="card shadow-sm mb-4 border-left-primary">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                <div class="d-flex align-items-center" style="min-width:160px;">
                    <label class="mb-0 mr-1 text-gray-700 small font-weight-bold" for="filter_code_rack">
                        <i class="fas fa-qrcode"></i>
                    </label>
                    <input type="text" class="form-control form-control-sm" id="filter_code_rack" placeholder="Code Rack" style="min-width:100px;">
                </div>
                <div class="d-flex align-items-center">
                    <select class="form-control form-control-sm" id="filter_status" style="min-width:120px;">
                        <option value="">Semua Status</option>
                        <option value="open">OPEN</option>
                        <option value="ready">READY</option>
                        <option value="closed">CLOSED</option>
                        <option value="cancelled">CANCELLED</option>
                    </select>
                </div>
                <div class="d-flex align-items-center">
                    <button id="btn-filter" class="btn btn-primary btn-sm mr-1">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="btn-reset">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-9">
                    <h6 class="m-0 font-weight-bold text-primary">Table List</h6>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-primary text-white">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <input type="text" class="form-control" id="table_search_keyword" placeholder="Search data...">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered text-center" id="sumTable" width="100%" cellspacing="0"
                    style="font-size: 13px;">
                    <thead class="bg-gradient-primary text-white">
                        <tr>
                            <th>No</th>
                            <th>Time Mismatch</th>
                            <th>Status</th>
                            <th>Code Rack</th>
                            <th style="min-width:90px;max-width:110px;">Name Part</th>
                            <th>Sum Request</th>
                            <th>Received</th>
                            <th>Outstanding</th>
                            <th>Ready Date</th>
                            <th>Record DST</th>
                            <th>Sum Record</th>
                            <th>Reporter</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

@endsection

@section('script')
<!-- Page level plugins -->
<script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>

<script>
    $(document).ready(function () {
        var table = $('#sumTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            ajax: {
                url: "{{ route('sum.data') }}",
                data: function (d) {
                    d.codeRack = $('#filter_code_rack').val();
                    d.status = $('#filter_status').val();
                    d.keyword = $('#table_search_keyword').val();
                }
            },
            columns: [
                {
                    data: null,
                    name: 'no',
                    searchable: false,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return meta.settings.fnRecordsDisplay() - meta.row - meta.settings._iDisplayStart;
                    }
                },
                { data: 'Time_Mismatch', name: 'Time_Mismatch', render: function (data) { return data || '-'; } },
                { data: 'Status_Badge', name: 'Status_Badge', searchable: false },
                { data: 'Code_Rack', name: 'Code_Rack' },
                { data: 'Name_Part', name: 'Name_Part', searchable: false, orderable: false, className: 'name-part-col' },
                { data: 'Sum_Request', name: 'Sum_Request' },
                { data: 'Received_Qty', name: 'Received_Qty' },
                { data: 'Outstanding_Qty', name: 'Outstanding_Qty' },
                { data: 'Ready_Date', name: 'Ready_Date', searchable: false, render: function (data) { return data || '-'; } },
                { data: 'Record_DST', name: 'Record_DST', searchable: false, orderable: false },
                { data: 'Sum_Record', name: 'Sum_Record', searchable: false, orderable: false, render: function (data) { return data || '-'; } },
                { data: 'Reporter', name: 'Reporter', searchable: false },
                { data: 'Action', name: 'Action', searchable: false, orderable: false },
            ],
            order: [[1, 'desc']],
            searching: false,
        });

        $('#btn-filter').click(function () {
            table.draw();
        });

        $('#btn-reset').click(function () {
            $('#filter_code_rack').val('');
            $('#filter_status').val('');
            $('#table_search_keyword').val('');
            table.draw();
        });

        $('#filter_status').on('change', function () {
            table.draw();
        });

        $('#filter_code_rack').on('keypress', function (e) {
            if (e.which == 13) {
                table.draw();
            }
        });

        var searchTimer;
        $('#table_search_keyword').on('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                table.draw();
            }, 500);
        });
    });
</script>
@endsection