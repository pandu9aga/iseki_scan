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
                                        <span style="font-size: small;">Rack Code</span>
                                        <input type="text" name="Code_Rack" id="Code_Rack" class="form-control form-control-user @error('Code_Rack') is-invalid @enderror" value="{{ old('Code_Rack') }}" required>
                                        <br>
                                        <span style="font-size: small;">Item Code</span>
                                        <input type="text" name="Code_Item" id="Code_Item" class="form-control form-control-user @error('Code_Item') is-invalid @enderror" value="{{ old('Code_Item') }}" required>
                                        @error('Code_Rack')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Tambahan input Sum_Request -->
                            <div class="row">
                                <div class="col-lg-12 text-center">
                                    <div class="form-group mb-3">
                                        <label for="Sum_Request" style="font-size: small;">Sum Request</label>
                                        <input type="number" name="Sum_Request" id="Sum_Request" class="form-control form-control-user @error('Sum_Request') is-invalid @enderror" value="{{ old('Sum_Request', 1) }}" min="1" step="1" required>
                                        @error('Sum_Request')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-12 text-center">
                                    <div class="form-group mb-3">
                                        <label for="Area_Request" style="font-size: small;">Area Request</label>
                                        <input type="text" name="Area_Request" id="Area_Request" class="form-control form-control-user @error('Area_Request') is-invalid @enderror" value="{{ old('Area_Request', $area) }}" readonly>
                                        @error('Area_Request')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="Correctness" name="Correctness" value="">

                            <hr>
                            <span id="status_code" class="status"></span>
                            <hr>
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

</div>
<!-- /.container-fluid -->

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
                } else {
                    document.getElementById("Code_Item").value = '';
                    alert('Code Item not found for this Code Rack');
                }
            },
            error: function() {
                alert('Error fetching Code Item');
            }
        });
    }

    // === callback qr scanner ===
    function onScanSuccessRack(decodedText, decodedResult) {
        document.getElementById("Code_Rack").value = decodedText;

        // panggil fetch
        fetchCodeItemRack(decodedText);

        rackScanner.clear();
        // makeCodeRack();
    }

    // === tombol scan ===
    document.getElementById("scanRack").addEventListener("click", function () {
        // let imgElement = document.querySelector("#qrcode_rack img");
        // if (imgElement) {
        //     imgElement.src = "";
        // }
        rackScanner.render(onScanSuccessRack);
    });

    // === generate qr ===
    // function makeCodeRack() {
    //     var rackText = document.getElementById("Code_Rack");
    //     qrcode_rack.makeCode(rackText.value);
    // }

    // === saat blur ===
    $("#Code_Rack").on("blur", function () {
        let codeRack = $(this).val();
        // makeCodeRack();
        fetchCodeItemRack(codeRack); // panggil juga
    });
</script>
@endsection

@section('style')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
