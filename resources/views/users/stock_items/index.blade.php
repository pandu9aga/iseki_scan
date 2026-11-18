@extends('layouts.user')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Stock Item</h1>
    </div>

    <!-- Alert Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Scan Tambah Stock Item -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-qrcode mr-1"></i> Scan Tambah Stock Item
            </h6>
        </div>
        <div class="card-body">
            <div id="reader_stock" class="mx-auto" style="max-width: 300px;"></div>
            <div class="text-center mt-3">
                <button type="button" id="scanStockBtn" class="btn btn-warning btn-sm">
                    <i class="fas fa-qrcode mr-1"></i> Scan
                </button>
            </div>
            <small class="text-muted d-block text-center mt-2">
                Scan kode rak untuk menambahkannya ke Stock Item.
            </small>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Data Stock Item</h6>
            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                <!-- Add Manual -->
                <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#addManualModal">
                    <i class="fas fa-plus mr-1"></i> Add Manual
                </button>
                <!-- Bulk Delete -->
                <button type="button" class="btn btn-danger btn-sm" id="btnBulkDelete" style="display:none;" onclick="submitBulkDelete()">
                    <i class="fas fa-trash mr-1"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Search -->
            <form method="GET" action="{{ route('user_stock_item') }}" class="form-inline mb-3">
                <div class="input-group" style="width: 320px; max-width: 100%;">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari Code Rack..."
                        value="{{ $search ?? '' }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('user_stock_item') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" id="checkAll" onclick="toggleCheckAll(this)">
                            </th>
                            <th style="width:60px;">No</th>
                            <th>Code Rack</th>
                            <th style="width:160px;">Input By</th>
                            <th style="width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stockItems as $index => $item)
                            <tr>
                                <td class="text-center">
                                    @if (session('Id_Member') == $item->Id_Member)
                                        <input type="checkbox" class="row-check" value="{{ $item->Id_Stock_Item }}" onclick="updateBulkButton()">
                                    @else
                                        <i class="fas fa-lock text-muted" title="Bukan data Anda"></i>
                                    @endif
                                </td>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->Code_Rack_Stock_Item }}</td>
                                <td>{{ $members[$item->Id_Member] ?? '-' }}</td>
                                <td class="text-center">
                                    @if (session('Id_Member') == $item->Id_Member)
                                        <form action="{{ route('user_stock_item.destroy', $item->Id_Stock_Item) }}" method="POST"
                                            onsubmit="return confirm('Yakin hapus data Code Rack: {{ $item->Code_Rack_Stock_Item }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted" title="Hanya bisa menghapus data yang Anda inputkan">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data stock item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<!-- Modal Add Manual -->
<div class="modal fade" id="addManualModal" tabindex="-1" role="dialog" aria-labelledby="addManualModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('user_stock_item.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="addManualModalLabel">
                        <i class="fas fa-plus mr-1"></i> Add Manual Stock Item
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="code_racks">Code Rack <span class="text-danger">*</span></label>
                        <textarea name="code_racks" id="code_racks" class="form-control" rows="6"
                            placeholder="Masukkan Code Rack&#10;Satu per baris untuk multiple input&#10;&#10;Contoh:&#10;RACK-001&#10;RACK-002&#10;RACK-003" required></textarea>
                    </div>
                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="fas fa-info-circle mr-1"></i>
                            Masukkan satu Code Rack per baris.<br>
                            Bisa juga dipisahkan dengan koma (,) atau titik koma (;).<br>
                            Data duplikat akan otomatis dilewati.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Form for Bulk Delete -->
<form id="bulkDeleteForm" action="{{ route('user_stock_item.bulk_destroy') }}" method="POST" style="display:none;">
    @csrf
</form>

<!-- Hidden Form for Scan Add -->
<form id="scanStockForm" action="{{ route('user_stock_item.store') }}" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="code_racks" id="scanStockCode">
</form>

@endsection

@section('style')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('script')
<!-- DataTables -->
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            pageLength: 100,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
            order: [[1, 'asc']]
        });
    });

    // Toggle semua checkbox
    function toggleCheckAll(source) {
        var checkboxes = document.querySelectorAll('.row-check');
        checkboxes.forEach(function(cb) {
            cb.checked = source.checked;
        });
        updateBulkButton();
    }

    // Update tombol bulk delete
    function updateBulkButton() {
        var checked = document.querySelectorAll('.row-check:checked');
        var btn = document.getElementById('btnBulkDelete');
        var count = document.getElementById('selectedCount');

        if (checked.length > 0) {
            btn.style.display = 'inline-block';
            count.textContent = checked.length;
        } else {
            btn.style.display = 'none';
            count.textContent = '0';
        }
    }

    // Submit bulk delete
    function submitBulkDelete() {
        var checked = document.querySelectorAll('.row-check:checked');
        if (checked.length === 0) {
            alert('Tidak ada data yang dipilih.');
            return;
        }

        if (!confirm('Yakin hapus ' + checked.length + ' data yang dipilih?')) return;

        var form = document.getElementById('bulkDeleteForm');
        // Remove previous hidden inputs
        form.querySelectorAll('input[name="ids[]"]').forEach(function(el) { el.remove(); });

        checked.forEach(function(cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            form.appendChild(input);
        });

        form.submit();
    }
</script>
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
    let stockScanner = new Html5QrcodeScanner("reader_stock", {
        fps: 10,
        qrbox: { width: 220, height: 220 }
    });

    function onScanStockSuccess(decodedText) {
        document.getElementById('scanStockCode').value = decodedText.toUpperCase().trim();
        stockScanner.clear();
        document.getElementById('scanStockForm').submit();
    }

    document.getElementById('scanStockBtn').addEventListener('click', function () {
        stockScanner.render(onScanStockSuccess);
    });
</script>
@endsection
