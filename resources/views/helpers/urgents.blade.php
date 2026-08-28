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
    .border-left-pink {
        border-left: .25rem solid #e83e8c !important;
    }
    .text-pink {
        color: #e83e8c !important;
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
        <h1 class="h3 mb-0 text-gray-800">Urgent List</h1>
        <div>
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm mr-2" id="btn-export">
                <i class="fas fa-file-excel fa-sm text-white-50"></i> Export Excel
            </a>
            <a href="{{ route('urgents.unrecorded') }}" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
                <i class="fas fa-list fa-sm text-white-50"></i> View Unrecorded List
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="card shadow-sm mb-4 border-left-primary">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                <div class="d-flex align-items-center flex-grow-1" style="min-width:160px;">
                    <label class="mb-0 mr-1 text-gray-700 small font-weight-bold" for="filter_code_rack">
                        <i class="fas fa-qrcode"></i>
                    </label>
                    <input type="text" class="form-control form-control-sm" id="filter_code_rack" placeholder="Code Rack" style="min-width:100px;">
                </div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-date-prev" title="Sebelumnya">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <input type="date" id="filter_date_urgent" class="form-control form-control-sm mx-1" style="width:auto;min-width:130px;max-width:160px;" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-date-next" title="Selanjutnya">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info ml-1" id="btn-date-today" title="Hari Ini">
                        <i class="fas fa-calendar-day"></i>
                    </button>
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

    <!-- Daily Recap -->
    <div class="row" id="daily-recap-container">
        <!-- populated by JS -->
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
                            <th>Type Tractor</th>
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
                { data: 'Type_Tractor', name: 'Type_Tractor', className: 'name-part-col' },
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
                { data: 'Record_Time', name: 'Record_Time', searchable: false },
            ],
            order: [[1, 'desc']], // Order by time by default (newest first)
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
            setTodayDate();
            $('#table_search_keyword').val('');
            table.draw();
            fetchRecap();
        });

        // Date navigation: prev/next/today
        function formatDate(d) {
            return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
        }
        function setTodayDate() {
            $('#filter_date_urgent').val(formatDate(new Date()));
        }
        function applyFilter() {
            table.draw();
            fetchRecap();
        }
        $('#btn-date-prev').click(function () {
            var d = new Date($('#filter_date_urgent').val() + 'T00:00:00');
            d.setDate(d.getDate() - 1);
            $('#filter_date_urgent').val(formatDate(d));
            applyFilter();
        });
        $('#btn-date-next').click(function () {
            var d = new Date($('#filter_date_urgent').val() + 'T00:00:00');
            d.setDate(d.getDate() + 1);
            $('#filter_date_urgent').val(formatDate(d));
            applyFilter();
        });
        $('#btn-date-today').click(function () {
            setTodayDate();
            applyFilter();
        });
        // Auto-filter on date change
        $('#filter_date_urgent').on('change', function () {
            applyFilter();
        });

        // Enter on inputs
        $('#filter_code_rack').on('keypress', function(e) {
            if(e.which == 13) {
                table.draw();
                fetchRecap();
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

        // Export button
        $('#btn-export').click(function(e) {
            e.preventDefault();
            var dateUrgent = $('#filter_date_urgent').val();
            var codeRack = $('#filter_code_rack').val();
            var keyword = $('#table_search_keyword').val();
            
            var url = "{{ route('urgents.export') }}?dateUrgent=" + encodeURIComponent(dateUrgent);
            if(codeRack) {
                url += "&codeRack=" + encodeURIComponent(codeRack);
            }
            if(keyword) {
                url += "&keyword=" + encodeURIComponent(keyword);
            }
            window.location.href = url;
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
            var qcHtml = generateCardHtml('QC', data.qc, 'pink');
            var dstHtml = generateCardHtml('DST', data.dst, 'info');
            var reportersHtml = generateReporterCardHtml(data);

            var html = `
                <div class="col-12 mb-2">
                    <h4 class="text-gray-900 font-weight-bold">${title}</h4>
                </div>
                ${bossMcHtml}
                ${qcHtml}
                ${dstHtml}
                ${reportersHtml}
            `;
            $(containerId).html(html);
        }

        function generateCardHtml(title, dat, colorClass) {
            var catHtml = '';
            for(var key in dat.categories) {
                catHtml += `<div class="mb-1 d-flex" style="gap:8px; font-size: 15px;">
                                <span class="font-weight-bold text-gray-900">${key}:</span>
                                <span class="text-gray-950 font-weight-bold">${dat.categories[key]}</span>
                            </div>`;
            }
            if(Object.keys(dat.categories).length === 0) {
                catHtml = '<span class="text-muted font-italic" style="font-size: 15px;">Blank (0 Items)</span>';
            }

            return `
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-${colorClass} shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center mb-3">
                                <div class="col mr-2">
                                    <div class="text-sm font-weight-bold text-${colorClass} text-uppercase mb-1" style="font-size: 14px;">
                                        PIC: ${title}
                                    </div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-900">
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

        function generateReporterCardHtml(data) {
            var catHtml = '';
            for(var key in data.reporters) {
                catHtml += `<div class="mb-1 d-flex" style="gap:8px; font-size: 15px;">
                                <span class="font-weight-bold text-gray-900">${key}:</span>
                                <span class="text-gray-950 font-weight-bold">${data.reporters[key]}</span>
                            </div>`;
            }
            if(Object.keys(data.reporters).length === 0) {
                catHtml = '<span class="text-muted font-italic" style="font-size: 15px;">Blank (0 Items)</span>';
            }

            return `
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center mb-3">
                                <div class="col mr-2">
                                    <div class="text-sm font-weight-bold text-success text-uppercase mb-1" style="font-size: 14px;">
                                        REPORTERS
                                    </div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-900">
                                        Total: ${data.reporters_total}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
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
