@extends('layouts.main')

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    table.dataTable th,
    table.dataTable td {
        white-space: nowrap;
        padding: 0.3rem;
        font-size: 14px;
    }

    #dataTable th.no-col,
    #dataTable td.no-col {
        width: 40px !important;
        min-width: 40px !important;
        max-width: 40px !important;
        text-align: center;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Page Title --}}
    <h1 class="h3 mb-2 text-gray-800">Check List - Advanced Search</h1>

    {{-- Action Buttons --}}
    <div class="d-sm-flex align-items-center justify-content-between mb-1">
        <a class="d-sm-inline-block btn btn-md btn-primary shadow-sm m-3"
            href="{{ route('admin.check') }}">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back
        </a>
        <button class="d-sm-inline-block btn btn-md btn-success shadow-sm m-3"
            data-toggle="modal"
            data-target="#downloadExcelModal"
            type="button">
            <i class="fas fa-file-excel fa-sm text-white-50"></i> Download Excel
        </button>
    </div>

    {{-- Table Card --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Check Advanced Search</h6>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover"
                    id="dataTable"
                    cellspacing="0"
                    width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Time Check</th>
                            <th>Code Rack</th>
                            <th>Code Item</th>
                            <th>Name Item</th>
                            <th>Checker</th>
                            <th>Area Check</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Modal Download Excel --}}
<div class="modal fade" id="downloadExcelModal" tabindex="-1" role="dialog"
    aria-labelledby="downloadExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="downloadExcelModalLabel">
                    <i class="fas fa-file-excel mr-2"></i> Download Excel - Advanced Search
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('admin.check.export_search') }}"
                method="GET"
                target="_blank"
                id="exportSearchForm">
                <div class="modal-body">

                    <div class="form-group">
                        <label for="exportStatus">
                            <strong>Status <span class="text-danger">*</span></strong>
                        </label>
                        <select class="form-control" id="exportStatus" name="status">
                            <option value="">Semua Status</option>
                            <option value="scan">Scan (Belum Selesai)</option>
                            <option value="selesai">Selesai</option>
                            <option value="pending">Pending (Ada Target Hari)</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="exportStartDate">
                                <strong>Start Date <span class="text-danger">*</span></strong>
                            </label>
                            <input type="date" class="form-control"
                                id="exportStartDate"
                                name="start_date"
                                required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="exportEndDate">
                                <strong>End Date <span class="text-danger">*</span></strong>
                            </label>
                            <input type="date" class="form-control"
                                id="exportEndDate"
                                name="end_date"
                                required>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 mb-0">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Data yang didownload akan difilter berdasarkan status dan range tanggal Time Check.
                        </small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-download mr-1"></i> Download
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<script>
    $(document).ready(function() {

        // ─── Data dari controller ────────────────────────────────────────────────
        const people = @json($people);

        // ─── Destroy jika sudah ada instance sebelumnya ──────────────────────────
        if ($.fn.dataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable().destroy();
        }

        // ─── Inisialisasi DataTables ─────────────────────────────────────────────
        var table = $('#dataTable').DataTable({

            processing: true,
            serverSide: true,
            pageLength: 100,
            autoWidth: false,

            // Kolom & urutan default
            // Index : 0=No | 1=Time Check | 2=Code Rack | 3=Code Item
            //         4=Name Item | 5=Checker | 6=Area Check | 7=Status
            columns: [{
                    data: null,
                    name: 'No',
                    orderable: false,
                    searchable: false,
                    width: '40px',
                    className: 'text-center no-col'
                },
                {
                    data: 'Time_Check',
                    name: 'Time_Check',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'Code_Rack',
                    name: 'Code_Rack',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'Code_Item_Rack',
                    name: 'Code_Item_Rack',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'Name_Item',
                    name: 'Name_Item',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'checker_name',
                    name: 'Id_User',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'Area_Check',
                    name: 'Area_Check',
                    orderable: true,
                    searchable: true
                },
                {
                    data: 'Status_Display',
                    name: 'Status_Check',
                    orderable: false,
                    searchable: false
                },
            ],

            order: [
                [1, 'desc']
            ], // default sort: Time Check terbaru

            // AJAX — ambil data dari server
            ajax: {
                url: "{{ route('admin.check.search') }}",
                type: 'GET',
                data: function(d) {
                    // Kirim nilai filter Status ke server
                    d.statusFilter = $('.status-filter').val();
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr.responseText);
                    alert('Gagal memuat data. Cek console untuk detail.');
                }
            },

            // Nomor urut manual (reset tiap draw)
            drawCallback: function() {
                this.api()
                    .column(0, {
                        search: 'applied',
                        order: 'applied'
                    })
                    .nodes()
                    .each(function(cell, i) {
                        cell.innerHTML = i + 1;
                    });
            },

            // Scroll
            scrollY: '480px',
            scrollX: true,
            scrollCollapse: true,

            // Bahasa Indonesia
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Data _START_–_END_ dari _TOTAL_',
                infoEmpty: 'Tidak ada data',
                zeroRecords: 'Data tidak ditemukan',
                emptyTable: 'Belum ada data',
                processing: 'Memuat...',
                paginate: {
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya'
                }
            },

            // Filter row per kolom
            initComplete: function() {
                var api = this.api();
                var header = $(api.table().header());

                // Buat filter row jika belum ada
                if (header.find('tr.filter-row').length === 0) {
                    header.append('<tr class="filter-row"></tr>');
                }

                var filterRow = header.find('tr.filter-row');
                filterRow.empty();

                // Kolom yang punya filter
                // 2=Code Rack | 3=Code Item | 5=Checker | 6=Area Check | 7=Status
                // 0,1,4 tidak punya filter (No, Time Check, Name Item)
                const filterable = [2, 3, 5, 6, 7];

                api.columns().every(function(index) {
                    var column = this;

                    // Kolom tanpa filter → kosongkan th-nya
                    if (!filterable.includes(index)) {
                        filterRow.append('<th></th>');
                        return;
                    }

                    // Index 5 → Checker: dropdown list member
                    if (index === 5) {
                        var select = $('<select class="form-control form-control-sm member-filter"><option value="">Semua Checker</option></select>');
                        people.forEach(function(person) {
                            select.append('<option value="' + person.id + '">' + person.name + '</option>');
                        });
                        select.on('change', function() {
                            column.search(this.value).draw();
                        });
                        filterRow.append($('<th>').append(select));

                        // Index 7 → Status: dropdown status (reload ajax karena custom param)
                    } else if (index === 7) {
                        var statusSelect = $([
                            '<select class="form-control form-control-sm status-filter">',
                            '<option value="">Semua Status</option>',
                            '<option value="scan">Scan</option>',
                            '<option value="selesai">Selesai</option>',
                            '<option value="pending">Pending</option>',
                            '</select>'
                        ].join(''));
                        statusSelect.on('change', function() {
                            table.ajax.reload();
                        });
                        filterRow.append($('<th>').append(statusSelect));

                        // Index 2, 3, 6 → Code Rack, Code Item, Area Check: input text
                    } else {
                        var input = $('<input type="text" class="form-control form-control-sm" placeholder="Cari..." />');
                        input.on('keyup change clear', function() {
                            column.search(this.value).draw();
                        });
                        filterRow.append($('<th>').append(input));
                    }
                });
            }

        });

        // ─── Sinkronisasi lebar kolom header & body saat scroll ─────────────────
        function syncColumnWidths() {
            var $headTable = $('.dataTables_scrollHead table');
            var $bodyTable = $('.dataTables_scrollBody table');

            $bodyTable.find('tbody tr:first-child td').each(function(i) {
                $headTable.find('thead th:eq(' + i + ')').outerWidth($(this).outerWidth());
            });
        }

        $('#dataTable').on('draw.dt', function() {
            setTimeout(syncColumnWidths, 100);
        });

    });
</script>
@endsection