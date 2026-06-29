@extends('layouts.user')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800" id="top">Request</h1>

    <div id="reader_rack" class="mx-auto" style="max-width: 300px;"></div>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <div class="p-5">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <span class="badge bg-success">Success</span> {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <span class="badge bg-danger">Error</span> {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif
                        <form class="user text-center" action="{{ route('request.create') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 col-md-12 text-center">
                                    <div class="form-group mb-3">
                                        <div>
                                            {{-- <span style="font-size: small;">QR Code Rack</span>
                                            <div id="parent_qrcode" class="container-fluid d-flex justify-content-start p-0" style="max-width: 150px;">
                                                <div id="qrcode_rack"></div>
                                            </div>
                                            <br> --}}
                                            <a href="#top">
                                                <button type="button" id="scanRack" class="btn btn-warning btn-sm">
                                                    Scan
                                                </button>
                                            </a>
                                        </div>

                                        {{-- Check Buttons: 1-4 Hari --}}
                                        <div class="mb-2 mt-4 d-flex justify-content-center flex-wrap align-items-center" style="gap: 5px;">
                                            <button type="button" class="btn btn-sm" style="background-color:#28a745;color:#fff;padding:4px 10px;font-weight:600;" onclick="submitCheck(1)">
                                                1 Hari
                                            </button>
                                            <button type="button" class="btn btn-sm" style="background-color:#17a2b8;color:#fff;padding:4px 10px;font-weight:600;" onclick="submitCheck(2)">
                                                2 Hari
                                            </button>
                                            <button type="button" class="btn btn-sm" style="background-color:#ffc107;color:#fff;padding:4px 10px;font-weight:600;" onclick="submitCheck(3)">
                                                3 Hari
                                            </button>
                                            <button type="button" class="btn btn-sm" style="background-color:#dc3545;color:#fff;padding:4px 10px;font-weight:600;" onclick="submitCheck(4)">
                                                4 Hari
                                            </button>

                                            <div class="input-group input-group-sm" style="width: 120px; margin-left: 5px;">
                                                <input type="number" id="customCheckDays" class="form-control" placeholder="Hari" min="1" style="text-align:center;">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-secondary" onclick="submitCustomCheck()">Set</button>
                                                </div>
                                            </div>
                                        </div>

                                        <span style="font-size: small;">Rack Code</span>
                                        <input type="text" onkeyup="this.value = this.value.toUpperCase();" name="Code_Rack" id="Code_Rack" class="form-control form-control-user mb-2 @error('Code_Rack') is-invalid @enderror" value="{{ old('Code_Rack', $code_rack ?? '') }}" required>
                                        <span style="font-size: small;">Item Code</span>
                                        <input type="text" name="Code_Item" id="Code_Item" class="form-control form-control-user @error('Code_Item') is-invalid @enderror" value="{{ old('Code_Item', $code_item ?? '') }}" readonly required>
                                        <span style="font-size: small;">Tractor Type</span>
                                        <input type="text" name="Type_Tractor" id="Type_Tractor" class="form-control form-control-user" readonly>
                                        @error('Code_Rack')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Tambahan input Sum_Request -->
                            <div class="row">
                                <div class="col-8 text-center">
                                    <div class="form-group mb-1">
                                        <label for="Sum_Request" style="font-size: small;">Sum Request</label>
                                        <input type="number" name="Sum_Request" id="Sum_Request" class="form-control form-control-user @error('Sum_Request') is-invalid @enderror" value="{{ old('Sum_Request', 1) }}" min="1" step="1" required>
                                        @error('Sum_Request')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="form-group mb-1">
                                        <label class="form-check-label ms-2" for="Urgent_Request" style="font-size: small;">
                                                Urgent Request
                                            </label>
                                        <div class="form-check d-flex justify-content-center">
                                            <input type="checkbox" 
                                                name="Urgent_Request" 
                                                id="Urgent_Request" 
                                                class="form-check-input"
                                                value="1"
                                                {{ old('Urgent_Request') ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12 text-center">
                                    <div class="form-group mb-2">
                                        <label for="Area_Request" style="font-size: small;">Area Request</label>
                                        <input type="text" name="Area_Request" id="Area_Request" class="form-control form-control-user @error('Area_Request') is-invalid @enderror" value="{{ old('Area_Request', $area) }}" readonly>
                                        @error('Area_Request')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="Correctness" name="Correctness" value="">
                            <input type="hidden" name="check_id" value="{{ $check_id ?? '' }}">
                            <input type="hidden" name="return_to_check" value="{{ !empty($check_id) ? 1 : 0 }}">
                            <input type="hidden" name="date" value="{{ $filter_date ?? '' }}">
                            <input type="hidden" name="target_date" value="{{ $filter_target_date ?? '' }}">
                            <input type="hidden" name="status" value="{{ $filter_status ?? '' }}">
                            <input type="hidden" name="checker" value="{{ $filter_checker ?? '' }}">

                            <hr>
                            <span id="status_code" class="status"></span>
                            <div class="row">
                                <div class="col-lg-3 col-md-3 text-center"></div>
                                <div class="col-lg-6 col-md-6 text-center">
                                    <button type="submit" class="btn btn-info btn-user" style="padding-left: 50px; padding-right: 50px;">
                                        Save
                                    </button>
                                </div>
                                <div class="col-lg-3 col-md-3 text-center"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Data View Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-database mr-1"></i>Data Request
                </h6>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDateReq(-1)" title="Sebelumnya">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <input type="date" id="filterDateReq" class="form-control form-control-sm mx-1" style="width:auto;min-width:130px;max-width:160px;" onchange="loadRequestData()">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDateReq(1)" title="Selanjutnya">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info ml-1" onclick="setTodayReq()" title="Hari Ini">
                        <i class="fas fa-calendar-day"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-hover" id="requestDataTable" style="font-size:13px;">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center" style="width:40px;">No</th>
                                <th>Code Item</th>
                                <th>Code Rack</th>
                                <th>Area</th>
                                <th class="text-center">Sum</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Time</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody id="requestDataBody">
                            <tr><td colspan="8" class="text-center text-muted py-4">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="requestDataInfo" class="text-muted small mt-1"></div>
            </div>
        </div>

    </div>
    <!-- /.container-fluid -->

<!-- Modal Duplicate Request Warning -->
<div class="modal fade" id="duplicateRequestModal" tabindex="-1" role="dialog" aria-labelledby="duplicateRequestModalLabel" aria-hidden="true" style="display:none;">
    <div class="modal-dialog" role="document" style="display:flex; align-items:center; justify-content:center; min-height:100vh;">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="duplicateRequestModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Peringatan Request Ganda
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="resetRequestForm()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="duplicateRequestBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="resetRequestForm()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Stock Check (informasi stok sudah ada) -->
<div class="modal fade" id="stockCheckModal" tabindex="-1" role="dialog" aria-labelledby="stockCheckModalLabel" aria-hidden="true" style="display:none;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="stockCheckModalLabel">
                    ✅ Stock
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div style="font-size: 48px;">✅</div>
                <h4 class="mt-2 text-success font-weight-bold">Stock Tersedia</h4>
                <p class="mb-0">Code Rack ini sudah ada di Stock Item.<br>Tidak perlu di-request lagi.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal">OK, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Reset form saat klik di luar modal (backdrop click)
    document.addEventListener('click', function(e) {
        var modal = document.getElementById('duplicateRequestModal');
        if (e.target === modal) {
            resetRequestForm();
        }
    });
</script>

<!-- Select2 & DataTables -->
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/select2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();
        $('.select2').select2({
            placeholder: "Select Member (Optional)",
            allowClear: true,
            width: '100%'
        });
    });
</script>

<!-- QR Code Library -->
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/qrcode.min.js') }}"></script>

<script>
    // var element = document.getElementById('reader_rack');
    // var width = element.offsetWidth;
    var width = 150;

    let rackScanner = new Html5QrcodeScanner(
        "reader_rack", {
            fps: 10,
            qrbox: {
                width: width,
                height: width,
            },
        }
    );

    // var qrcode_rack = new QRCode("qrcode_rack", {
    //     width: width,
    //     height: width
    // });

    // === fungsi reset form ke state awal ===
    function resetRequestForm() {
        document.getElementById("Code_Rack").value = '';
        document.getElementById("Code_Item").value = '';
        document.getElementById("Type_Tractor").value = '';
        document.getElementById("Sum_Request").value = 1;
        document.getElementById("Urgent_Request").checked = false;
        document.getElementById("customCheckDays").value = '';
        document.getElementById("Correctness").value = '';
        document.getElementById("status_code").innerHTML = '';
    }

    // === fungsi fetch code item ===
    function fetchCodeItemRack(codeRack) {
        if (!codeRack) return;

        $.ajax({
            url: './api/get-code-item',  
            method: 'POST',
            data: {
                code_rack: codeRack,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if(response.code_item) {
                    document.getElementById("Code_Item").value = response.code_item;
                    document.getElementById("Type_Tractor").value = response.type_tractor ?? '';
                    // Setelah berhasil fetch code item, cek stock
                    checkStockItem(codeRack);
                } else {
                    document.getElementById("Code_Item").value = '';
                    document.getElementById("Type_Tractor").value = '';
                    alert('Code Item not found for this Code Rack');
                }
            },
            error: function() {
                alert('Error fetching Code Item');
            }
        });
    }

    // === fungsi cek duplicate request ===
    function checkDuplicateRequest(codeRack, onClear) {
        if (!codeRack) return;

        $.ajax({
            url: '{{ route("request.checkDuplicate") }}',
            method: 'POST',
            data: {
                Code_Rack: codeRack,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.exists) {
                    // Tampilkan modal warning
                    var msg = 'Part dengan kode Rack <strong>' + codeRack + '</strong> telah di request oleh <strong>' + response.name + '</strong> pada <strong>' + response.day + ' ' + response.time + '</strong> dan belum di Record.';
                    document.getElementById('duplicateRequestBody').innerHTML = msg;
                    $('#duplicateRequestModal').modal('show');
                } else {
                    // Tidak ada duplikat, lanjut fetch code item
                    fetchCodeItemRack(codeRack);
                }
            },
            error: function() {
                // Jika error cek duplikat, tetap lanjut fetch code item
                fetchCodeItemRack(codeRack);
            }
        });
    }



    // === callback qr scanner ===
    function onScanSuccessRack(decodedText, decodedResult) {
        document.getElementById("Code_Rack").value = decodedText;

        // Cek duplikat dulu, baru fetch code item jika tidak ada duplikat
        checkDuplicateRequest(decodedText);

        rackScanner.clear();
    }

    // === tombol scan ===
    document.getElementById("scanRack").addEventListener("click", function () {
        rackScanner.render(onScanSuccessRack);
    });

    // === saat blur ===
    $("#Code_Rack").on("blur", function () {
        let codeRack = $(this).val();
        checkDuplicateRequest(codeRack);
    });

    // === fungsi cek stock item ===
    function checkStockItem(codeRack) {
        if (!codeRack) return;

        $.ajax({
            url: './api/check-stock-item',
            method: 'POST',
            data: {
                code_rack: codeRack,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.in_stock) {
                    $('#stockCheckModal').modal('show');
                }
            }
        });
    }

    // === Check Mid/Lot submit ===
    function submitCheck(statusCheck) {
        var codeRack = document.getElementById("Code_Rack").value;
        var codeItem = document.getElementById("Code_Item").value;

        if (!codeRack || !codeItem) {
            alert('Silakan scan atau isi Rack Code terlebih dahulu.');
            return;
        }

        // Tampilkan modal konfirmasi
        var label = statusCheck + ' Hari Ke Depan';
        document.getElementById('checkConfirmLabel').textContent = label;
        document.getElementById('checkConfirmRack').textContent = codeRack;
        document.getElementById('confirmCheckDays').value = statusCheck;
        $('#checkConfirmModal').modal('show');
    }

    function submitCustomCheck() {
        var days = document.getElementById("customCheckDays").value;
        if (!days || days < 1) {
            alert('Masukkan jumlah hari yang valid (minimal 1).');
            return;
        }
        submitCheck(days);
    }

    document.getElementById('btnConfirmCheck').addEventListener('click', function() {
        var days = document.getElementById('confirmCheckDays').value;
        var codeRack = document.getElementById("Code_Rack").value;
        var codeItem = document.getElementById("Code_Item").value;
        document.getElementById('check_Code_Rack').value = codeRack;
        document.getElementById('check_Code_Item').value = codeItem;
        document.getElementById('check_Status').value = days;
        $('#checkConfirmModal').modal('hide');
        document.getElementById('checkForm').submit();
    });
</script>

<script>
function loadRequestData() {
    var date = document.getElementById('filterDateReq').value;
    if (!date) return;
    $.get('{{ route("request.data") }}', { date: date }, function(res) {
        var tbody = document.getElementById('requestDataBody');
        var info = document.getElementById('requestDataInfo');
        tbody.innerHTML = '';
        if (res.requests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Tidak ada request pada tanggal ini</td></tr>';
            info.textContent = 'Total: 0 request'; return;
        }
        res.requests.forEach(function(r, i) {
            var s = '';
            if (r.status === 'Waiting') s = '<span class="badge badge-warning">Waiting</span>';
            else if (r.status === 'Done') s = '<span class="badge badge-success">Done</span>';
            else s = '<span class="badge badge-secondary">' + (r.status || '-') + '</span>';
            tbody.innerHTML += '<tr>' +
                '<td class="text-center">' + (i + 1) + '</td>' +
                '<td>' + (r.code_item || '') + '</td>' +
                '<td>' + (r.code_rack || '') + '</td>' +
                '<td>' + (r.area || '-') + '</td>' +
                '<td class="text-center font-weight-bold">' + (r.sum_request || 0) + '</td>' +
                '<td class="text-center">' + s + '</td>' +
                '<td class="text-center">' + (r.time ? r.time.substr(0,5) : '-') + '</td>' +
                '<td>' + (r.user || '-') + '</td></tr>';
        });
        info.textContent = 'Total: ' + res.count + ' request(s)';
    }).fail(function() {
        document.getElementById('requestDataBody').innerHTML =
            '<tr><td colspan="8" class="text-center text-danger py-3">Gagal memuat data</td></tr>';
    });
}
function changeDateReq(offset) {
    var d = new Date(document.getElementById('filterDateReq').value + 'T00:00:00');
    d.setDate(d.getDate() + offset);
    document.getElementById('filterDateReq').value = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    loadRequestData();
}
function setTodayReq() {
    var d = new Date();
    document.getElementById('filterDateReq').value = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    loadRequestData();
}
document.addEventListener('DOMContentLoaded', function() { setTodayReq(); });
</script>

{{-- Hidden form for Check submission --}}
<form id="checkForm" action="{{ route('user.check.store') }}" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="Code_Rack" id="check_Code_Rack">
    <input type="hidden" name="Code_Item" id="check_Code_Item">
    <input type="hidden" name="Status_Check" id="check_Status">
    <input type="hidden" name="check_id" value="{{ $check_id ?? '' }}">
    <input type="hidden" name="date" value="{{ $filter_date ?? '' }}">
    <input type="hidden" name="target_date" value="{{ $filter_target_date ?? '' }}">
    <input type="hidden" name="status" value="{{ $filter_status ?? '' }}">
    <input type="hidden" name="checker" value="{{ $filter_checker ?? '' }}">
</form>

@endsection

@section('style')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/select2.min.css') }}" rel="stylesheet">
@endsection

{{-- Modal Konfirmasi Check --}}
<div class="modal fade" id="checkConfirmModal" tabindex="-1" role="dialog" aria-labelledby="checkConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="checkConfirmModalLabel">
                    <i class="fas fa-clipboard-check mr-2"></i>Konfirmasi Check
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                <p class="mb-1">Simpan Check untuk Rack:</p>
                <h5 class="font-weight-bold text-dark mb-2"><code id="checkConfirmRack"></code></h5>
                <p class="mb-0">Target: <span class="badge badge-primary px-3 py-2" style="font-size:1rem;" id="checkConfirmLabel"></span></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Batal
                </button>
                <button type="button" class="btn btn-primary px-4" id="btnConfirmCheck">
                    <i class="fas fa-check mr-1"></i>Ya, Simpan Check!
                </button>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="confirmCheckDays" value="">
