@extends('layouts.user')
@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800">Request</h1>
        <div class="d-sm-flex align-items-center justify-content-between mb-1">
            <a class="d-sm-inline-block btn btn-md btn-primary shadow-sm m-3" href="{{ route('submission') }}">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Request Search</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-fixed table-bordered table-sm table-hover" id="dataTable" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Area</th>
                                <th>Member Request</th>
                                <th>Item</th>
                                <th>Rack <span class="text-white">-------</span></th>
                                <th>Type Tractor</th>
                                <th>Time Request</th>
                                <th>Ready Stock</th>
                                <th>Time Record</th>
                                <th>Status Request</th>
                                <th>Sum Request</th>
                                <th>Urgenity</th>
                                <th>Name</th>
                                <th>Sum Record</th>
                                <th>Member Record</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    <style>
        /* Hanya atur font & padding */
        table.dataTable th,
        table.dataTable td {
            white-space: nowrap;
            padding: 0.3rem;
            font-size: 14px;
        }

        /* Optional: pastikan card tidak terlalu tinggi */
        .card-body {
            padding: 1rem;
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

@section('script')
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <script>
        $(document).ready(function () {
            // Kirim daftar member ke JS
            const members = @json($members); // ← ini penting!

            if ($.fn.dataTable.isDataTable('#dataTable')) {
                $('#dataTable').DataTable().destroy();
            }

            var table = $('#dataTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 100,
                autoWidth: false,
                ajax: {
                    url: "{{ route('submission.search') }}",
                    type: 'GET',
                    data: function (d) {
                        d.statusFilter = $('.filter-row select').eq(1).val();
                    },
                    error: function (xhr) {
                        console.error('AJAX Error:', xhr.responseText);
                        alert('Failed to load data. See console for details.');
                    }
                },
                columns: [
                    { data: null, name: 'No', orderable: false, searchable: false, width: '40px', className: 'text-center no-col' },
                    { data: 'Area_Request', name: 'Area_Request', searchable: true },
                    { data: 'Member_Request', name: 'Id_User', searchable: true }, // ← penting!
                    { data: 'Code_Item_Rack', name: 'Code_Item_Rack', searchable: true },
                    { data: 'Code_Rack', name: 'Code_Rack', searchable: true, width: 'auto' },
                    { data: 'Type_Tractor_Rack', name: 'Type_Tractor_Rack', searchable: false },
                    { data: 'Day_Request', name: 'Day_Request', searchable: false },
                    { data: 'ready_status_display', name: 'ready_status_display', searchable: false },
                    { data: 'Time_Record', name: 'Time_Record', searchable: false },
                    { data: 'Status_Request_Display', name: 'Status_Request_Display', searchable: false },
                    { data: 'Sum_Request', name: 'Sum_Request', searchable: false },
                    { data: 'Urgent_Request', name: 'Urgent_Request', searchable: false },
                    { data: 'Name', name: 'Name', searchable: false },
                    { data: 'Sum_Record', name: 'Sum_Record', searchable: false },
                    { data: 'Member_Record', name: 'Member_Record', searchable: false },
                    { data: 'Updated_At_Request', name: 'Updated_At_Request', searchable: false }
                ],
                order: [[6, 'desc']], // urutkan berdasarkan Time Request
                drawCallback: function () {
                    this.api().column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                },
                // Nonaktifkan fitur yang bentrok
                scrollY: '480px',
                scrollY: true,
                scrollX: true,
                scrollCollapse: true,
                initComplete: function () {
                    var api = this.api();
                    var header = $(api.table().header());

                    // Buat baris filter
                    if (header.find('tr').length === 1) {
                        var filterRow = $('<tr class="filter-row"></tr>');
                        header.append(filterRow);
                    }

                    var filterRow = header.find('tr.filter-row');
                    filterRow.empty();

                    const filterable = [1, 2, 3, 4, 7];

                    api.columns().every(function (index) {
                        var column = this;

                        if (!filterable.includes(index)) {
                            filterRow.append('<th></th>');
                            return;
                        }

                        if (index === 2) {
                            // Kolom Member Request → dropdown
                            var select = $(`<select class="form-control form-control-sm"><option value="">All</option></select>`);
                            members.forEach(member => {
                                select.append(`<option value="${member.Id_Member}">${member.Name_Member}</option>`);
                            });
                            select.on('change', function () {
                                column.search(this.value).draw();
                            });
                            filterRow.append($('<th>').append(select));
                        } else if (index === 7) {
                            // Kolom Ready Stock → status dropdown
                            var select = $(`<select class="form-control form-control-sm">
                                                    <option value="">All Status</option>
                                                    <option value="ready">Ready</option>
                                                    <option value="shipping">Shipping</option>
                                                    <option value="production">Production</option>
                                                    <option value="design_change">Design Change</option>
                                                </select>`);
                            select.on('change', function () {
                                table.ajax.reload();
                            });
                            filterRow.append($('<th>').append(select));
                        } else {
                            // Kolom lain → input text
                            var input = $(`<input type="text" class="form-control form-control-sm" placeholder="Search" />`)
                                .on('keyup change clear', function () {
                                    column.search(this.value).draw();
                                });
                            filterRow.append($('<th>').append(input));
                        }
                    });
                }
            });

            function syncColumnWidths() {
                var headerTable = $('.DTFC_LeftHeadWrapper table, .dataTables_scrollHead table');
                var bodyTable = $('.DTFC_LeftBodyWrapper table, .dataTables_scrollBody table');

                // Ambil lebar tiap kolom dari body (yang akurat)
                bodyTable.find('tbody tr:first-child td').each(function (i) {
                    var width = $(this).outerWidth();
                    // Terapkan ke header
                    headerTable.find('thead th:eq(' + i + ')').outerWidth(width);
                });
            }

            // Panggil setelah draw
            $('#dataTable').on('draw.dt', function () {
                setTimeout(function () {
                    if (table.fixedColumns) {
                        table.fixedColumns().relayout();
                    }
                    syncColumnWidths(); // tambahan
                }, 100);
            });
        });
    </script>
@endsection