@extends('layouts.qc')

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
    .badge-ok  { font-size: 0.7rem; padding: 3px 7px; }

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

    /* ── Autocomplete ────────────────────────────────────── */
    .autocomplete-wrapper { position: relative; }
    .autocomplete-results {
        position: absolute;
        top: 100%; left: 0; right: 0;
        z-index: 9999;
        background: white;
        border: 1px solid #ccc;
        max-height: 220px;
        overflow-y: auto;
        display: none;
        border-radius: 4px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    }
    .autocomplete-results.show { display: block; }
    .autocomplete-item {
        padding: 9px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
    }
    .autocomplete-item:hover, .autocomplete-item.selected { background: #f0f7ff; }
    .autocomplete-main { font-weight: 700; color: #2d4a8a; font-size: 0.9rem; }
    .autocomplete-sub  { font-size: 0.78rem; color: #666; }
    .autocomplete-loading, .autocomplete-empty { padding: 10px; text-align: center; color: #888; }

    /* ── Zebra striping override ─────────────────────────── */
    tbody tr:nth-child(even) .td-wd   { background-color: #fffae8; }
    tbody tr:nth-child(even) .td-dst  { background-color: #e8f2ff; }
    tbody tr:nth-child(even) .td-rcv  { background-color: #e8faee; }
    tbody tr:nth-child(even) .td-ret  { background-color: #f6f0ff; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h4 class="text-primary font-weight-bold mb-2 mb-md-0">
            <i class="fas fa-exchange-alt mr-2"></i>QC Withdrawal
        </h4>
        <div>
            <a href="{{ route('qc.withdrawal.export', request()->all()) }}" class="btn btn-success mr-2">
                <i class="fas fa-download mr-1"></i> Export Excel
            </a>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalPengajuan">
                <i class="fas fa-plus mr-1"></i> Pengajuan Baru
            </button>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm mb-3 border-left-primary">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('qc.withdrawal') }}" class="form-inline d-flex align-items-center flex-wrap">
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
                    <a href="{{ route('qc.withdrawal') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sync-alt"></i> Reset</a>
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
    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="withdrawalTable" width="100%">
                    <thead>
                        {{-- Column Header Row --}}
                        <tr>
                            <th class="th-group-wd" style="min-width:30px;">No</th>
                            <th class="th-group-wd" style="min-width:140px;">PIC Withdrawal</th>
                            <th class="th-group-wd" style="min-width:140px;">Item Code</th>
                            <th class="th-group-wd" style="min-width:50px;">Aksi</th>
                            
                            <th class="th-group-dst" style="min-width:110px;">Oke DST</th>
                            <th class="th-group-dst" style="min-width:110px;">PIC DST</th>
                            
                            <th class="th-group-rcv" style="min-width:110px;">Received</th>
                            <th class="th-group-rcv" style="min-width:110px;">Finish</th>
                            
                            <th class="th-group-ret" style="min-width:120px;">PIC Return</th>
                            <th class="th-group-ret" style="min-width:100px;">No Rack Return</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($withdrawals as $index => $w)
                        <tr>
                            <td class="td-wd">{{ $index + 1 }}</td>

                            {{-- WITHDRAWAL QC --}}
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
                            <td class="td-wd">
                                @if(!$w->Oke_Withdrawal)
                                    <form action="{{ route('qc.withdrawal.destroy', $w->Id_Withdrawal) }}" method="POST" onsubmit="return confirm('Hapus data pengajuan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-action" title="Hapus Pengajuan">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted" title="Sudah di-OKE DST"><i class="fas fa-lock"></i></span>
                                @endif
                            </td>

                            {{-- PREPARE BY DST — READ ONLY (QC monitors) --}}
                            <td class="td-dst">
                                @if($w->Oke_Withdrawal)
                                    <span class="chip chip-done"><i class="fas fa-check mr-1"></i>OK</span><br>
                                    <small class="text-muted d-block mt-1">
                                        {{ \Carbon\Carbon::parse($w->Date_Withdrawal)->format('d/m/y H:i') }}
                                    </small>
                                @else
                                    <span class="chip chip-wait">Menunggu</span>
                                @endif
                            </td>
                            <td class="td-dst">{{ $w->name_disiapkan ?? '-' }}</td>

                            {{-- RECEIVED BY QC --}}
                            <td class="td-rcv">
                                @if($w->Oke_Receiving)
                                    <span class="chip chip-done"><i class="fas fa-check mr-1"></i>Ya</span><br>
                                    <small class="text-muted d-block mt-1">
                                        {{ \Carbon\Carbon::parse($w->Date_Receiving)->format('d/m/y H:i') }}
                                    </small>
                                @elseif($w->Oke_Withdrawal)
                                    <form action="{{ route('qc.withdrawal.receiving', $w->Id_Withdrawal) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-action"
                                            onclick="return confirm('Konfirmasi barang sudah diterima QC?')">
                                            <i class="fas fa-hand-holding mr-1"></i>Terima
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="td-rcv">
                                @if($w->Finish_Receiving)
                                    <span class="chip chip-done"><i class="fas fa-check-double mr-1"></i>Ya</span><br>
                                    <small class="text-muted d-block mt-1">
                                        {{ \Carbon\Carbon::parse($w->Date_Finish_Receiving)->format('d/m/y H:i') }}
                                    </small>
                                @elseif($w->Oke_Receiving)
                                    <form action="{{ route('qc.withdrawal.finish', $w->Id_Withdrawal) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-info btn-action"
                                            onclick="return confirm('Konfirmasi QC selesai menggunakan barang?')">
                                            <i class="fas fa-flag-checkered mr-1"></i>Selesai
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- RETURN TO RACK — READ ONLY for QC (DST does the action) --}}
                            <td class="td-ret">
                                @if($w->Date_Return)
                                    <span class="font-weight-bold">{{ $w->name_return ?? $w->NIK_Return }}</span><br>
                                    <small class="text-muted d-block mt-1">
                                        {{ \Carbon\Carbon::parse($w->Date_Return)->format('d/m/y H:i') }}
                                    </small>
                                @elseif($w->Finish_Receiving)
                                    <span class="chip chip-active">Menunggu DST</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="td-ret">
                                {{ $w->Code_Rack_Return ?? '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Pengajuan Baru --}}
<div class="modal fade" id="modalPengajuan" tabindex="-1" role="dialog" aria-labelledby="modalPengajuanLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('qc.withdrawal.store') }}" method="POST" id="formPengajuan">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Pengajuan Withdrawal Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama PIC QC <span class="text-danger">*</span></label>
                        <input type="text" name="Name_Withdrawal" class="form-control"
                            placeholder="Masukkan nama PIC" value="{{ old('Name_Withdrawal') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Kode Part <span class="text-danger">*</span></label>
                        <div class="autocomplete-wrapper">
                            <input type="text" id="Code_Item_Withdrawal" name="Code_Item_Withdrawal"
                                class="form-control" placeholder="Ketik kode part / nama item / no rack..."
                                value="{{ old('Code_Item_Withdrawal') }}" autocomplete="off" required>
                            <div id="autocomplete-results" class="autocomplete-results"></div>
                        </div>
                        <small class="text-muted">Kode part harus terdaftar di data rak.</small>
                    </div>
                    {{-- Live preview after select --}}
                    <div id="rackPreview" class="alert alert-info py-2 px-3 mt-2" style="display:none; font-size:0.85rem;">
                        <i class="fas fa-tag mr-1"></i>
                        <strong id="prevItemCode"></strong> — <span id="prevItemName"></span><br>
                        <i class="fas fa-warehouse mr-1"></i> No Rack: <code id="prevRackNo"></code>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Simpan Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
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

    // ── Autocomplete Kode Part ──────────────────────────────
    const $input   = $('#Code_Item_Withdrawal');
    const $results = $('#autocomplete-results');
    const $preview = $('#rackPreview');
    let timeout, selectedIndex = -1, results = [];

    $input.on('input', function() {
        const q = $(this).val().trim();
        clearTimeout(timeout);
        selectedIndex = -1;
        $preview.hide();
        if (q.length < 1) { $results.removeClass('show').empty(); return; }
        timeout = setTimeout(() => search(q), 280);
    });

    $input.on('keydown', function(e) {
        if (!$results.hasClass('show')) return;
        const $items = $results.find('.autocomplete-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, $items.length - 1);
            updateSel($items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSel($items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0) selectItem(results[selectedIndex]);
        } else if (e.key === 'Escape') {
            $results.removeClass('show');
        }
    });

    $(document).on('click', '.autocomplete-item', function() {
        selectItem(results[$(this).data('index')]);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.autocomplete-wrapper').length) {
            $results.removeClass('show');
        }
    });

    function search(q) {
        $results.html('<div class="autocomplete-loading"><i class="fas fa-spinner fa-spin mr-1"></i>Mencari...</div>').addClass('show');
        $.ajax({
            url:  '{{ route("qc.withdrawal.searchRack") }}',
            data: { query: q },
            success: function(data) { results = data; renderResults(); },
            error:   function()     { $results.html('<div class="autocomplete-empty text-danger"><i class="fas fa-exclamation-circle mr-1"></i>Gagal memuat data</div>'); }
        });
    }

    function renderResults() {
        if (results.length === 0) {
            $results.html('<div class="autocomplete-empty">Tidak ada hasil ditemukan</div>');
            return;
        }
        const html = results.map((item, i) => `
            <div class="autocomplete-item" data-index="${i}">
                <div class="autocomplete-main">${esc(item.item_code)}</div>
                <div class="autocomplete-sub">
                    ${item.part_name ? esc(item.part_name) + ' &nbsp;|&nbsp; ' : ''}
                    Rack: <code>${esc(item.rack_no)}</code>
                </div>
            </div>`).join('');
        $results.html(html).addClass('show');
    }

    function updateSel($items) {
        $items.removeClass('selected');
        if (selectedIndex >= 0) $items.eq(selectedIndex).addClass('selected')[0].scrollIntoView({block:'nearest'});
    }

    function selectItem(item) {
        if (!item) return;
        $input.val(item.item_code);
        $results.removeClass('show').empty();
        // Show preview
        $('#prevItemCode').text(item.item_code);
        $('#prevItemName').text(item.part_name || '-');
        $('#prevRackNo').text(item.rack_no);
        $preview.show();
    }

    function esc(text) {
        if (!text) return '';
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    // Clear preview when modal closes
    $('#modalPengajuan').on('hidden.bs.modal', function() {
        $preview.hide();
        $results.removeClass('show').empty();
        $input.val('');
    });

});
</script>
@endsection
