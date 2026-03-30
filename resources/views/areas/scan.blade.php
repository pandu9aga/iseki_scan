@extends('layouts.area')

@section('content')
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Scan Code Rack</h1>
    </div>

    <div class="row">
        <div class="col-lg-6">
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
                            <label for="Code_Rack">Scan Rack Code (Barcode/QR)</label>
                            <!-- Make it readonly so no manual input allowed -->
                            <input type="text" class="form-control form-control-user" id="Code_Rack" name="Code_Rack" placeholder="Result..." readonly required>
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
<script src="{{ asset('js/jquery.min.js') }}"></script>
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
        document.getElementById("scanForm").submit();
    }

    // button scan
    document.getElementById("scanRack").addEventListener("click", function () {
        rackScanner.render(onScanSuccessRack);
    });

</script>
@endsection
