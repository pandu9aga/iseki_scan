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
    .badge-pink {
        background-color: #e83e8c;
        color: white;
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
    <div class="d-sm-flex align-items-center justify-content-between mb-2">
        <h1 class="h3 mb-0 text-gray-800">Unrecorded Urgent List</h1>
        <a href="javascript:history.back()" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Urgent List
        </a>
    </div>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Data</h6>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="filter_code_rack">Code Rack</label>
                    <input type="text" class="form-control" id="filter_code_rack" placeholder="Code Rack...">
                </div>
                <div class="form-group col-md-3">
                    <label for="filter_date_urgent">Date Urgent</label>
                    <input type="date" class="form-control" id="filter_date_urgent" value="">
                </div>
                <div class="form-group col-md-6 d-flex align-items-end">
                    <button id="btn-filter" class="btn btn-primary mr-2">Filter</button>
                    <button class="btn btn-secondary mr-2" id="btn-reset">Reset</button>
                    <button id="btn-export" class="btn btn-success"><i class="fas fa-file-excel mr-1"></i> Export Excel</button>
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
                <table class="table table-bordered text-center" id="urgentsTable" width="100%" cellspacing="0"
                    style="font-size: 14px;">
                    <thead class="bg-gradient-primary text-white">
                        <tr>
                            <th>No</th>
                            <th>Time Urgent</th>
                            <th>Category</th>
                            <th>Code Rack</th>
                            <th style="min-width:90px;max-width:110px;">Name Part</th>
                            <th>PIC</th>
                            <th>Reporter</th>
                            <th>Request Details</th>
                            <th>Request Time</th>
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
        // Init DataTable
        var table = $('#urgentsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 100,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            ajax: {
                url: "{{ route('urgents.unrecorded.data') }}",
                data: function (d) {
                    d.codeRack = $('#filter_code_rack').val();
                    d.dateUrgent = $('#filter_date_urgent').val();
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
                { data: 'Time_Urgent', name: 'Time_Urgent' },
                { data: 'Mistake_Category', name: 'Mistake_Category', searchable: false },
                { data: 'Code_Rack', name: 'Code_Rack' },
                { 
                    data: 'Name_Part', 
                    name: 'Name_Part', 
                    searchable: false,
                    orderable: false,
                    className: 'name-part-col'
                },
                { 
                    data: 'PIC_Urgent', 
                    name: 'PIC_Urgent', 
                    searchable: false,
                    createdCell: function (td, cellData, rowData, row, col) {
                        if (cellData === 'Boss MC') {
                            $(td).css('background-color', '#f6c23e');
                        } else if (cellData === 'QC') {
                            $(td).css({'background-color': '#e83e8c', 'color': '#fff'});
                        }
                    }
                },
                { data: 'Reporter', name: 'Reporter', searchable: false },
                { data: 'Request_Details', name: 'Request_Details', searchable: false },
                { data: 'Request_Time', name: 'Request_Time', searchable: false },
            ],
            order: [[1, 'desc']], // Order by time by default (newest first)
            searching: false, // Turn off default global search
        });

        // Filter button
        $('#btn-filter').click(function () {
            table.draw();
        });

        // Reset button
        $('#btn-reset').click(function () {
            $('#filter_code_rack').val('');
            $('#filter_date_urgent').val('');
            $('#table_search_keyword').val('');
            table.draw();
        });

        $('#btn-export').click(function(e) {
            e.preventDefault();
            let codeRack = $('#filter_code_rack').val();
            let dateUrgent = $('#filter_date_urgent').val();
            
            let exportUrl = "{{ route('urgents.unrecorded.export') }}?codeRack=" + encodeURIComponent(codeRack) + "&dateUrgent=" + encodeURIComponent(dateUrgent);
            window.location.href = exportUrl;
        });

        // Enter on inputs
        $('#filter_code_rack, #filter_date_urgent').on('keypress', function(e) {
            if(e.which == 13) {
                table.draw();
            }
        });

        // Keyup on search keyword with debounce
        var searchTimer;
        $('#table_search_keyword').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                table.draw();
            }, 500);
        });
    });
</script>
@endsection
