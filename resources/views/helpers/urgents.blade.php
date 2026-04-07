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
                    <input type="date" class="form-control" id="filter_date_urgent" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
                </div>
                <div class="form-group col-md-4 d-flex align-items-end">
                    <button id="btn-filter" class="btn btn-primary mr-2">Filter</button>
                    <button class="btn btn-secondary" id="btn-reset">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Recap -->
    <div class="row" id="daily-recap-container">
        <!-- populated by JS -->
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
                            <th style="min-width:90px;max-width:110px;">Name Part</th>
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

<!-- Monthly Recap -->
<div class="container-fluid">
    <div class="row mt-2" id="monthly-recap-container">
        <!-- populated by JS -->
    </div>
</div>

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
            fetchRecap();
        });

        // Reset button
        $('#btn-reset').click(function () {
            $('#filter_code_rack').val('');
            $('#filter_date_urgent').val('{{ \Carbon\Carbon::today()->format("Y-m-d") }}');
            table.draw();
            fetchRecap();
        });

        // Enter on inputs
        $('#filter_code_rack, #filter_date_urgent').on('keypress', function(e) {
            if(e.which == 13) {
                table.draw();
                fetchRecap();
            }
        });

        function fetchRecap() {
            var dateInput = $('#filter_date_urgent').val();
            $.ajax({
                url: '{{ route("urgents.recap") }}',
                data: { dateUrgent: dateInput },
                success: function(res) {
                    renderRecapCard('#daily-recap-container', 'Daily Recap: ' + res.date_formatted, res.daily);
                    renderRecapCard('#monthly-recap-container', 'Monthly Recap: ' + res.month_formatted, res.monthly);
                }
            });
        }

        function renderRecapCard(containerId, title, data) {
            $(containerId).empty();
            var bossMcHtml = generateCardHtml('Boss MC', data.boss_mc, 'warning');
            var dstHtml = generateCardHtml('DST', data.dst, 'info');

            var html = `
                <div class="col-12 mb-2">
                    <h5 class="text-gray-800 font-weight-bold">${title}</h5>
                </div>
                ${bossMcHtml}
                ${dstHtml}
            `;
            $(containerId).html(html);
        }

        function generateCardHtml(title, dat, colorClass) {
            var catHtml = '';
            for(var key in dat.categories) {
                catHtml += `<div class="d-flex justify-content-between mb-1">
                                <span class="small font-weight-bold text-gray-800">${key}</span>
                                <span class="small text-gray-800">${dat.categories[key]}</span>
                            </div>`;
            }
            if(Object.keys(dat.categories).length === 0) {
                catHtml = '<span class="small text-muted">Blank (0 Items)</span>';
            }

            return `
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-left-${colorClass} shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center mb-3">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-${colorClass} text-uppercase mb-1">
                                        PIC: ${title}
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        Total: ${dat.total}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            <hr>
                            ${catHtml}
                        </div>
                    </div>
                </div>
            `;
        }

        // Initial fetch
        fetchRecap();
    });
</script>
@endsection
