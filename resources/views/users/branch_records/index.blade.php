@extends('layouts.user')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800" id="top">Branch Record</h1>

    <div id="reader_rack" class="mx-auto" style="max-width: 300px;"></div>
    <!-- Form Card -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <div class="p-4">
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

                        <form class="user text-center" action="{{ route('branch_record.create') }}" id="branchRecordForm"
                            method="POST">
                            @csrf
                            <div class="row justify-content-center">
                                <div class="col-lg-6 col-md-8 text-center">
                                    <div class="form-group mb-3">
                                        <div class="mb-2">
                                            <a href="#top">
                                                <button type="button" id="scanRack" class="btn btn-warning btn-md px-4 shadow-sm">
                                                    <i class="fas fa-qrcode mr-1"></i> Scan Rak
                                                </button>
                                            </a>
                                        </div>
                                        <label for="Code_Rack" class="small text-muted font-weight-bold">Nomor Rak </label>
                                        <input type="text" name="Code_Rack"
                                            onkeyup="this.value = this.value.toUpperCase();" id="Code_Rack"
                                            class="form-control form-control-user text-center font-weight-bold @error('Code_Rack') is-invalid @enderror"
                                            placeholder="Scan atau ketik No Rak..."
                                            value="{{ old('Code_Rack') }}" required autofocus>
                                        @error('Code_Rack')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-lg-4 col-md-3 text-center"></div>
                                <div class="col-lg-4 col-md-6 text-center">
                                    <button type="submit" class="btn btn-info btn-user btn-block shadow-sm">
                                        <i class="fas fa-save mr-1"></i> Simpan Branch Record
                                    </button>
                                </div>
                                <div class="col-lg-4 col-md-3 text-center"></div>
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
                <i class="fas fa-database mr-1"></i>Data Branch Record (Hasil Scan Hari Ini)
            </h6>
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDateBr(-1)" title="Sebelumnya">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <input type="date" id="filterDateBr" class="form-control form-control-sm mx-1" style="width:auto;min-width:130px;max-width:160px;" onchange="loadBranchData()">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeDateBr(1)" title="Selanjutnya">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-info ml-1" onclick="setTodayBr()" title="Hari Ini">
                    <i class="fas fa-calendar-day"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover" id="branchDataTable" style="font-size:13px;">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width:50px;">No</th>
                            <th>Code Rack</th>
                            <th class="text-center" style="width:120px;">Time</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody id="branchDataBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="branchDataInfo" class="text-muted small mt-1"></div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<!-- QR Code Library -->
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script src="{{ asset('js/jquery.min.js') }}"></script>

<script>
    var width = 200;

    let rackScanner = new Html5QrcodeScanner(
        "reader_rack", {
            fps: 10,
            qrbox: {
                width: width,
                height: width,
            },
        }
    );

    function onScanSuccessRack(decodedText, decodedResult) {
        document.getElementById("Code_Rack").value = decodedText.toUpperCase().trim();
        rackScanner.clear();
        document.getElementById("Code_Rack").focus();
    }

    document.getElementById("scanRack").addEventListener("click", function() {
        rackScanner.render(onScanSuccessRack);
    });
</script>

<script>
    function loadBranchData() {
        var date = document.getElementById('filterDateBr').value;
        if (!date) return;
        $.get('{{ route("branch_record.data") }}', {
            date: date
        }, function(res) {
            var tbody = document.getElementById('branchDataBody');
            var info = document.getElementById('branchDataInfo');
            tbody.innerHTML = '';
            if (res.records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada catatan rak pada tanggal ini</td></tr>';
                info.textContent = 'Total: 0 rak dicatat';
                return;
            }
            res.records.forEach(function(r, i) {
                tbody.innerHTML += '<tr>' +
                    '<td class="text-center">' + (i + 1) + '</td>' +
                    '<td class="font-weight-bold text-primary">' + (r.code_rack || '') + '</td>' +
                    '<td class="text-center">' + (r.time ? r.time.substr(0, 5) : '-') + '</td>' +
                    '<td>' + (r.user || '-') + '</td></tr>';
            });
            info.textContent = 'Total: ' + res.count + ' rak dicatat';
        }).fail(function() {
            document.getElementById('branchDataBody').innerHTML =
                '<tr><td colspan="4" class="text-center text-danger py-3">Gagal memuat data</td></tr>';
        });
    }

    function changeDateBr(offset) {
        var d = new Date(document.getElementById('filterDateBr').value + 'T00:00:00');
        d.setDate(d.getDate() + offset);
        document.getElementById('filterDateBr').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        loadBranchData();
    }

    function setTodayBr() {
        var d = new Date();
        document.getElementById('filterDateBr').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        loadBranchData();
    }
    document.addEventListener('DOMContentLoaded', function() {
        setTodayBr();
    });
</script>
@endsection