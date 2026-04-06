@extends($layout)
@section('style')
<link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
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
    <h1 class="h3 mb-2 text-gray-800">Urgent List</h1>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Data</h6>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="filter_code_rack">Code Rack</label>
                    <input type="text" class="form-control" id="filter_code_rack" placeholder="Code Rack...">
                </div>
                <div class="form-group col-md-4">
                    <label for="filter_date_urgent">Date Urgent</label>
                    <input type="date" class="form-control" id="filter_date_urgent">
                </div>
                <div class="form-group col-md-4 d-flex align-items-end">
                    <button id="btn-filter" class="btn btn-primary mr-2">Filter</button>
                    <button class="btn btn-secondary" id="btn-reset">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center" id="urgentsTable" width="100%" cellspacing="0"
                    style="font-size: 14px;">
                    <thead class="bg-gradient-primary text-white">
                        <tr>
                            <th>No</th>
                            <th>Time Urgent</th>
                            <th>Category</th>
                            <th>Code Rack</th>
                            <th>PIC</th>
                            <th>Reporter</th>
                            <th>Request Details</th>
                            <th>Request</th>
                            <th>Record</th>
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
                url: "{{ route('urgents.data') }}",
                data: function (d) {
                    d.codeRack = $('#filter_code_rack').val();
                    d.dateUrgent = $('#filter_date_urgent').val();
                }
            },
            columns: [
                {
                    data: null, 
                    name: 'no', 
                    searchable: false, 
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { data: 'Time_Urgent', name: 'Time_Urgent' },
                { data: 'Mistake_Category', name: 'Mistake_Category', searchable: false },
                { data: 'Code_Rack', name: 'Code_Rack' },
                { 
                    data: 'PIC_Urgent', 
                    name: 'PIC_Urgent', 
                    searchable: false,
                    createdCell: function (td, cellData, rowData, row, col) {
                        if (cellData === 'Boss MC') {
                            $(td).css('background-color', 'yellow');
                        }
                    }
                },
                { data: 'Reporter', name: 'Reporter', searchable: false },
                { data: 'Request_Details', name: 'Request_Details', searchable: false },
                { data: 'Request_Time', name: 'Request_Time', searchable: false },
                { data: 'Record_Time', name: 'Record_Time', searchable: false },
            ],
            order: [[1, 'desc']], // Order by time by default
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
            table.draw();
        });

        // Enter on inputs
        $('#filter_code_rack, #filter_date_urgent').on('keypress', function(e) {
            if(e.which == 13) {
                table.draw();
            }
        });
    });
</script>
@endsection
