@extends('layouts.main')

@section('style')
<link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
<style>
    /* ── Table Base ─────────────────────────────────────── */
    .table-responsive { overflow-x: auto; }
    .table th, .table td {
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
        font-size: 0.8rem;
        padding: 6px 8px;
    }
    .table td.text-left { text-align: left; }

    /* ── Column Group Headers ─────────────────────────────── */
    .th-group-wd   { background-color: #fff3cd !important; color: #856404; }
    .th-group-dst  { background-color: #cce5ff !important; color: #004085; }
    .th-group-rcv  { background-color: #d4edda !important; color: #155724; }
    .th-group-ret  { background-color: #e2d5f1 !important; color: #5a2d82; }

    .td-wd   { background-color: #fffdf0; }
    .td-dst  { background-color: #f0f7ff; }
    .td-rcv  { background-color: #f0fff4; }
    .td-ret  { background-color: #faf5ff; }

    /* ── Buttons ─────────────────────────────────────────── */
    .btn-action {
        font-size: 0.72rem;
        padding: 3px 8px;
        border-radius: 4px;
    }

    /* ── Status Chips ────────────────────────────────────── */
    .chip {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .chip-wait   { background:#ffeeba; color:#856404; }
    .chip-done   { background:#d4edda; color:#155724; }
    .chip-active { background:#cce5ff; color:#004085; }
    .chip-err    { background:#f8d7da; color:#721c24; }

    /* ── Zebra striping override ─────────────────────────── */
    tbody tr:nth-child(even) .td-wd   { background-color: #fffae8; }
    tbody tr:nth-child(even) .td-dst  { background-color: #e8f2ff; }
    tbody tr:nth-child(even) .td-rcv  { background-color: #e8faee; }
    tbody tr:nth-child(even) .td-ret  { background-color: #f6f0ff; }

    .scan-area { margin-top: 10px; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h4 class="text-primary font-weight-bold mb-2 mb-md-0">
            <i class="fas fa-exchange-alt mr-2"></i>QC Withdrawal <small class="text-muted" style="font-size:0.65em;">Admin</small>
        </h4>
        <a href="{{ route('admin.withdrawal.export', request()->all()) }}" class="btn btn-success btn-sm">
            <i class="fas fa-download mr-1"></i> Export Excel
        </a>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm mb-3 border-left-primary">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('admin.withdrawal') }}" class="form-inline d-flex align-items-center flex-wrap">
                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center">
                    <label class="mr-2 font-weight-bold text-gray-700" style="font-size:0.85rem;"><i class="fas fa-filter mr-1"></i>Filter:</label>
                    <select name="status" class="form-control form-control-sm" style="min-width: 150px;">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="unfinished" {{ request('status') == 'unfinished' ? 'selected' : '' }}>Belum Masuk Rak</option>
                        <option value="finished" {{ request('status') == 'finished' ? 'selected' : '' }}>Sudah Masuk Rak</option>
                    </select>
                </div>

                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center">
                    <label class="mr-2 text-gray-700" style="font-size:0.8rem;">Bulan:</label>
                    <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month') }}">
                </div>

                <div class="mr-3 mb-2 mb-md-0 mt-2 mt-md-0 d-flex align-items-center">
                    <label class="mr-2 text-gray-700" style="font-size:0.8rem;">Tanggal:</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                </div>

                <div class="mt-2 mt-md-0">
                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search"></i> Terapkan</button>
                    <a href="{{ route('admin.withdrawal') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sync-alt"></i> Reset</a>
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            @foreach($errors->all() as $error) {{ $error }}<br> @endforeach
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Main Table Card --}}
    <div class="card shadow border-bottom-primary">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="withdrawalTable" width="100%">
                    <thead>
                        {{-- Column Header Row --}}
                        <tr>
                            <th class="th-group-wd" style="min-width:30px;">No</th>
                            <th class="th-group-wd" style="min-width:140px;">PIC Withdrawal</th>
                            <th class="th-group-wd" style="min-width:140px;">Item Code</th>
                            
                            <th class="th-group-dst" style="min-width:110px;">Oke DST</th>
                            <th class="th-group-dst" style="min-width:110px;">PIC DST</th>
                            
                            <th class="th-group-rcv" style="min-width:110px;">Received</th>
                            <th class="th-group-rcv" style="min-width:110px;">Finish</th>
                            
                            <th class="th-group-ret" style="min-width:120px;">PIC Return</th>
                            <th class="th-group-ret" style="min-width:100px;">No Rack Return</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($withdrawals as $index => $w)
                        <tr>
                            <td class="td-wd">{{ $index + 1 }}</td>

                            {{-- WITHDRAWAL QC (Read Only) --}}
                            <td class="td-wd">
                                <span class="font-weight-bold">{{ $w->Name_Withdrawal ?? '-' }}</span><br>
                                <small class="text-muted d-block mt-1">
                                    {{ $w->Date_Withdrawal ? \Carbon\Carbon::parse($w->Date_Withdrawal)->format('d/m/y H:i') : '-' }}
                                </small>
                            </td>
                            <td class="td-wd">
                                <span class="font-weight-bold d-block">{{ $w->Code_Item_Withdrawal }}</span>
                                <span class="badge badge-dark mt-1">{{ $w->rack_name }}</span><br>
                                <span class="badge badge-secondary mt-1">{{ $w->rack_no }}</span>
                            </td>

                            {{-- PREPARE BY DST (Action for Admin) --}}
                            <td class="td-dst">
                                @if($w->Oke_Withdrawal)
                                    <span class="chip chip-active"><i class="fas fa-check mr-1"></i>OK</span><br>
                                    <small class="text-muted d-block mt-1">
                                        {{ \Carbon\Carbon::parse($w->Date_Withdrawal)->format('d/m/y H:i') }}
                                    </small>
                                @else
                                    <button class="btn btn-warning btn-action font-weight-bold" data-toggle="modal" data-target="#modalOke{{ $w->Id_Withdrawal }}">
                                        <i class="fas fa-hand-pointer mr-1"></i>OK Siapkan
                                    </button>
                                @endif
                            </td>
                            <td class="td-dst">{{ $w->name_disiapkan ?? '-' }}</td>

                            {{-- RECEIVED BY QC (Read Only for Admin) --}}
                            <td class="td-rcv">
                                @if($w->Oke_Receiving)
                                    <span class="chip chip-done"><i class="fas fa-check mr-1"></i>Diterima QC</span><br>
                                    <small class="text-muted d-block mt-1">
                                        {{ \Carbon\Carbon::parse($w->Date_Receiving)->format('d/m/y H:i') }}
                                    </small>
                                @else
                                    <span class="text-danger font-weight-bold" style="font-size:0.75rem;"><i class="fas fa-times mr-1"></i>Belum Diterima</span>
                                @endif
                            </td>
                            <td class="td-rcv">
                                @if($w->Finish_Receiving)
                                    <span class="chip chip-done"><i class="fas fa-check-double mr-1"></i>Selesai QC</span><br>
                                    <small class="text-muted d-block mt-1">
                                        {{ \Carbon\Carbon::parse($w->Date_Finish_Receiving)->format('d/m/y H:i') }}
                                    </small>
                                @else
                                    <span class="text-danger font-weight-bold" style="font-size:0.75rem;"><i class="fas fa-times mr-1"></i>Belum Selesai</span>
                                @endif
                            </td>

                            {{-- RETURN TO RACK (Action for Admin) --}}
                            <td class="td-ret">
                                @if($w->Date_Return)
                                    <span class="font-weight-bold">{{ $w->name_return ?? $w->NIK_Return }}</span><br>
                                    <small class="text-muted d-block mt-1">
                                        {{ \Carbon\Carbon::parse($w->Date_Return)->format('d/m/y H:i') }}
                                    </small>
                                @elseif($w->Finish_Receiving)
                                    <button class="btn btn-primary btn-action font-weight-bold" data-toggle="modal" data-target="#modalReturn{{ $w->Id_Withdrawal }}">
                                        <i class="fas fa-undo mr-1"></i>Masuk Rak
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="td-ret">
                                {{ $w->Code_Rack_Return ?? '-' }}
                            </td>
                        </tr>

                        {{-- Modal OK --}}
                        @if(!$w->Oke_Withdrawal)
                        <div class="modal fade" id="modalOke{{ $w->Id_Withdrawal }}" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-sm" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('admin.withdrawal.oke', $w->Id_Withdrawal) }}" method="POST">
                                        @csrf
                                        <div class="modal-header bg-warning">
                                            <h6 class="modal-title font-weight-bold">OK Siapkan Barang</h6>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <p style="font-size:0.85rem;" class="mb-2">
                                                Menyiapkan item <strong>{{ $w->Code_Item_Withdrawal }}</strong>.
                                            </p>
                                            <div class="form-group mb-2">
                                                <label style="font-size:0.82rem;font-weight:600;">NIK Member DST (Opsional jika ceklis Admin)</label>
                                                <input type="number" name="NIK_Withdrawal" class="form-control nik-input"
                                                    placeholder="Masukkan NIK Member" id="nikWd{{ $w->Id_Withdrawal }}">
                                                <div class="custom-control custom-checkbox mt-2">
                                                    <input type="checkbox" class="custom-control-input cb-admin" name="Is_User"
                                                        id="cbAdminWd{{ $w->Id_Withdrawal }}" value="1" data-target="#nikWd{{ $w->Id_Withdrawal }}">
                                                    <label class="custom-control-label" for="cbAdminWd{{ $w->Id_Withdrawal }}" style="font-size:0.82rem;font-weight:600;">
                                                        Centang jika disiapkan oleh Admin ({{ session('Username_User', 'Admin') }})
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer py-2">
                                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-check mr-1"></i>Konfirmasi OK
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Modal Masuk Rak --}}
                        @if($w->Finish_Receiving && !$w->Date_Return)
                        <div class="modal fade" id="modalReturn{{ $w->Id_Withdrawal }}" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('admin.withdrawal.return', $w->Id_Withdrawal) }}" method="POST">
                                        @csrf
                                        <div class="modal-header bg-primary text-white">
                                            <h6 class="modal-title font-weight-bold">
                                                <i class="fas fa-undo mr-1"></i>Masuk Rak — {{ $w->Code_Item_Withdrawal }}
                                            </h6>
                                            <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-warning py-2 mb-3" style="font-size:0.8rem;">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                Pastikan barcode rak yang discan adalah <strong>{{ $w->rack_no }}</strong>
                                            </div>
                                            <div class="form-group mb-2">
                                                <label style="font-size:0.82rem;font-weight:600;">NIK Member DST (Opsional jika ceklis Admin)</label>
                                                <input type="number" name="NIK_Return" class="form-control nik-input"
                                                    placeholder="Masukkan NIK Member" id="nikRet{{ $w->Id_Withdrawal }}">
                                                <div class="custom-control custom-checkbox mt-2">
                                                    <input type="checkbox" class="custom-control-input cb-admin" name="Is_User"
                                                        id="cbAdminRet{{ $w->Id_Withdrawal }}" value="1" data-target="#nikRet{{ $w->Id_Withdrawal }}">
                                                    <label class="custom-control-label" for="cbAdminRet{{ $w->Id_Withdrawal }}" style="font-size:0.82rem;font-weight:600;">
                                                        Centang jika dimasukkan oleh Admin ({{ session('Username_User', 'Admin') }})
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label style="font-size:0.82rem;font-weight:600;">Scan / Input Barcode Rak <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="text" name="Code_Rack_Return"
                                                        id="codeRack{{ $w->Id_Withdrawal }}"
                                                        class="form-control"
                                                        placeholder="Scan barcode rak atau ketik manual"
                                                        required autocomplete="off">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-secondary btnScan"
                                                            data-id="{{ $w->Id_Withdrawal }}"
                                                            title="Buka scanner kamera">
                                                            <i class="fas fa-camera"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- QR Scanner --}}
                                            <div id="readerRack{{ $w->Id_Withdrawal }}" class="scan-area"></div>
                                        </div>
                                        <div class="modal-footer py-2">
                                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save mr-1"></i>Simpan & Masuk Rak
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Belum ada data withdrawal.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
$(document).ready(function() {

    // DataTable
    var table = $('#withdrawalTable').DataTable({
        pageLength: 25,
        order: [], // Let it use backend ordering (Newest first)
        columnDefs: [
            { orderable: false, targets: 0 } // Disable sorting on "No" column
        ],
        language: {
            search: "Cari:", lengthMenu: "Tampilkan _MENU_ data",
            info: "Data _START_-_END_ dari _TOTAL_", infoEmpty: "Tidak ada data",
            zeroRecords: "Tidak ditemukan", emptyTable: "Belum ada data withdrawal",
            paginate: { next: "Selanjutnya", previous: "Sebelumnya" }
        }
    });

    // Auto-numbering
    table.on('order.dt search.dt', function () {
        let i = 1;
        table.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
            this.data(i++);
        });
    }).draw();

    // ── QR Scanner for Masuk Rak ────────────────────────────
    var scanners = {};

    $(document).on('click', '.btnScan', function() {
        var id = $(this).data('id');
        var readerId = 'readerRack' + id;

        if (scanners[id]) {
            scanners[id].clear();
            delete scanners[id];
            $('#' + readerId).empty();
            return;
        }

        scanners[id] = new Html5QrcodeScanner(readerId, {
            fps: 10,
            qrbox: { width: 240, height: 240 }
        });

        scanners[id].render(function(decodedText) {
            $('#codeRack' + id).val(decodedText.trim());
            scanners[id].clear();
            delete scanners[id];
            $('#' + readerId).empty();
        });
    });

    // Stop scanner when modal closes
    $('.modal').on('hidden.bs.modal', function() {
        var id = $(this).find('.btnScan').data('id');
        if (id && scanners[id]) {
            scanners[id].clear();
            delete scanners[id];
            $('#readerRack' + id).empty();
        }
    });

    // ── Checkbox Admin Flow ────────────────────────────────
    $(document).on('change', '.cb-admin', function() {
        var target = $(this).data('target');
        if($(this).is(':checked')) {
            $(target).prop('disabled', true).val('');
            $(target).removeAttr('required');
        } else {
            $(target).prop('disabled', false);
            $(target).attr('required', true);
            $(target).focus();
        }
    });

    // Initialize state on modal open
    $('.modal').on('show.bs.modal', function() {
        var $cb = $(this).find('.cb-admin');
        if($cb.length) {
            $cb.prop('checked', false).trigger('change');
        }
    });

});
</script>
@endsection
