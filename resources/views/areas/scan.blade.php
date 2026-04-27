@extends('layouts.area')

@section('content')
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Scan Code Rack</h1>
    </div>

    <style>
        .badge-pink {
            background-color: #e83e8c;
            color: white;
        }
    </style>

    <div class="row">
        <div class="col-lg-6">
            @if(session('success') && !session('scan_success_data'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- Modal Success -->
            <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content shadow border-0">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="successModalLabel">
                                <i class="fas fa-check-circle mr-2"></i> Scan Berhasil
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body py-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                                <h4 class="font-weight-bold text-success">Berhasil Diproses!</h4>
                                <p class="text-muted">Detail request untuk Kode Rak: <strong class="text-dark">{{ session('scan_success_data.code_rack') }}</strong></p>
                            </div>
                            
                            <hr>

                            @if(session('scan_success_data'))
                            <div class="px-3">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="45%"><i class="fas fa-receipt mr-2 text-primary"></i> Status</th>
                                        <td>: <span class="badge badge-{{ session('scan_success_data.badge_class') }} px-2">{{ session('scan_success_data.category') }}</span></td>
                                    </tr>
                                    <tr>
                                        <th><i class="fas fa-calendar-alt mr-2 text-primary"></i> Waktu Request</th>
                                        <td>: {{ session('scan_success_data.time_request') }}</td>
                                    </tr>
                                    <tr>
                                        <th><i class="fas fa-cubes mr-2 text-primary"></i> Jumlah Item</th>
                                        <td>: <strong>{{ session('scan_success_data.sum_request') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th><i class="fas fa-user mr-2 text-primary"></i> PIC Pemesan</th>
                                        <td>: {{ session('scan_success_data.pic') ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                            @endif
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-success btn-block font-weight-bold shadow-sm" data-dismiss="modal">Selesai</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Error -->
            <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content shadow">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="errorModalLabel">
                                <i class="fas fa-exclamation-triangle mr-2"></i> Error
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <h4 class="text-danger mb-3">Oops!</h4>
                            <p class="lead" id="errorMessage">{{ session('error') }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scan Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Scan Code Rack</h6>
                </div>
                <div class="card-body text-center">
                    <div id="reader_rack" class="mx-auto" style="max-width: 300px;"></div>
                    <br>
                    <button type="button" id="scanRack" class="btn btn-warning btn-sm mb-3">
                        Scan
                    </button>

                    <form action="{{ route('area.scan.process') }}" method="POST" id="scanForm">
                        @csrf
                        <div class="form-group text-left">
                            <label for="Code_Rack">Scan atau Ketik Kode Rak</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="Code_Rack" name="Code_Rack" placeholder="Ketik kode rak disini..." required autofocus style="text-transform: uppercase;">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="clearCode">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Gunakan scanner atau ketik langsung kode rak di atas.</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-user btn-block">
                            Proses Scan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<!-- QR Code Library -->
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script src="{{ asset('js/qrcode.min.js') }}"></script>

<script>
    var width = 250;

    let rackScanner = new Html5QrcodeScanner(
        "reader_rack", {
            fps: 10,
            qrbox: {
                width: width,
                height: width,
            },
        }
    );

    // callback qr scanner
    function onScanSuccessRack(decodedText, decodedResult) {
        document.getElementById("Code_Rack").value = decodedText;
        rackScanner.clear();
        
        // Auto process form when scan success
        disableSubmitAndSubmitForm();
    }

    // button scan
    document.getElementById("scanRack").addEventListener("click", function () {
        rackScanner.render(onScanSuccessRack);
    });

    // Clear input button
    document.getElementById("clearCode").addEventListener("click", function() {
        document.getElementById("Code_Rack").value = "";
        document.getElementById("Code_Rack").focus();
    });

    // Allow Enter key to submit (standard behavior, but being explicit)
    document.getElementById("Code_Rack").addEventListener("keypress", function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            disableSubmitAndSubmitForm();
        }
    });

    // Disable button if submitted via button click
    document.getElementById("scanForm").addEventListener("submit", function() {
        disableSubmitButton();
    });

    function disableSubmitButton() {
        var submitBtn = document.querySelector('#scanForm button[type="submit"]');
        if(submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
        }
        document.getElementById('Code_Rack').readOnly = true;
    }

    function disableSubmitAndSubmitForm() {
        disableSubmitButton();
        document.getElementById("scanForm").submit();
    }

    // Force uppercase on input
    document.getElementById("Code_Rack").addEventListener("input", function() {
        this.value = this.value.toUpperCase();
    });

    // Show error modal if session error exists
    @if(session('error'))
        $(document).ready(function() {
            $('#errorModal').modal('show');
        });
    @endif

    // Show success modal if scan data exists
    @if(session('scan_success_data'))
        $(document).ready(function() {
            $('#successModal').modal('show');
        });
    @endif

</script>
@endsection
