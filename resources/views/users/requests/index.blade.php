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
                        <form class="user text-center" action="{{ route('request.create') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 col-md-12 text-center">
                                    <div class="form-group mb-3">
                                        <div>
                                            <span style="font-size: small;">QR Code Rack</span>
                                            <div id="parent_qrcode" class="container-fluid d-flex justify-content-start p-0" style="max-width: 150px;">
                                                <div id="qrcode_rack"></div>
                                            </div>
                                            <br>
                                            <a href="#top">
                                                <button type="button" id="scanRack" class="btn btn-warning btn-sm">
                                                    Scan
                                                </button>
                                            </a>
                                        </div>
                                        <span style="font-size: small;">Rack Code</span>
                                        <input type="text" name="Code_Rack" id="Code_Rack" class="form-control form-control-user @error('Code_Rack') is-invalid @enderror" value="{{ old('Code_Rack') }}">
                                        <br>
                                        <span style="font-size: small;">Item Code</span>
                                        <input type="text" name="Code_Item" id="Code_Item" class="form-control form-control-user @error('Code_Item') is-invalid @enderror" value="{{ old('Code_Item') }}">
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
                                        <input type="number" name="Sum_Request" id="Sum_Request" class="form-control form-control-user @error('Sum_Request') is-invalid @enderror" value="{{ old('Sum_Request', 1) }}" min="1" step="1">
                                        @error('Sum_Request')
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
    var element = document.getElementById('parent_qrcode');
    var width = element.offsetWidth;

    let rackScanner = new Html5QrcodeScanner(
        "reader_rack", {
            fps: 10,
            qrbox: {
                width: width,
                height: width,
            },
        }
    );

    var qrcode_rack = new QRCode("qrcode_rack", {
        width: width,
        height: width
    });

    function onScanSuccessRack(decodedText, decodedResult) {
        document.getElementById("Code_Rack").value = decodedText;

        // AJAX request ke backend untuk dapat Code_Item
        $.ajax({
            url: '/api/get-code-item',  // route API yang akan dibuat
            method: 'POST',
            data: {
                code_rack: decodedText,
                _token: $('meta[name="csrf-token"]').attr('content') // pastikan token csrf ada
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

        rackScanner.clear();
        makeCodeRack();
        checkCorrectness();
    }

    document.getElementById("scanRack").addEventListener("click", function () {
        let imgElement = document.querySelector("#qrcode_rack img");
        if (imgElement) {
            imgElement.src = "";
        }
        rackScanner.render(onScanSuccessRack);
    });

    function makeCodeRack() {
        var rackText = document.getElementById("Code_Rack");
        qrcode_rack.makeCode(rackText.value);
    }

    $("#Code_Rack").on("blur", function () {
        makeCodeRack();
    });

    function checkCorrectness() {
        let itemValue = $("#Code_Item").val().trim();
        let rackValue = $("#Code_Rack").val().trim();
        let statusCode = $("#status_code");

        itemValue = itemValue.replace(/[^\w]/g, '');

        if (itemValue === "" || rackValue === "") {
            statusCode.html("").removeClass("bg-gradient-success bg-gradient-danger text-white");
            return;
        }

        $.get('./request/check', {
            Code_Rack: rackValue,
            Code_Item: itemValue
        }, function(response) {
            if (response.status === "correct") {
                statusCode
                    .html(`
                        <div style="font-size: 3rem;">✅</div>
                        <div style="font-size: 1.8rem; font-weight: bold;">Correct!</div>
                        <div style="font-size: 2rem;">😊</div>
                    `)
                    .removeClass("bg-gradient-danger")
                    .addClass("text-white bg-gradient-success p-3 rounded")
                    .css({
                        height: '180px',
                        display: 'flex',
                        'flex-direction': 'column',
                        'align-items': 'center',
                        'justify-content': 'center',
                        'text-align': 'center'
                    });

                statusCode[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                document.getElementById("Correctness").value = 1;
            } else {
                statusCode
                    .html(`
                        <div style="font-size: 3rem;">❌</div>
                        <div style="font-size: 1.8rem; font-weight: bold;">Incorrect!</div>
                        <div style="font-size: 2rem;">😢</div>
                    `)
                    .removeClass("bg-gradient-success")
                    .addClass("text-white bg-gradient-danger p-3 rounded")
                    .css({
                        height: '180px',
                        display: 'flex',
                        'flex-direction': 'column',
                        'align-items': 'center',
                        'justify-content': 'center',
                        'text-align': 'center'
                    });

                statusCode[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                document.getElementById("Correctness").value = 2;
            }
        });
    }

    $("#Code_Item, #Code_Rack").on("blur", checkCorrectness);
</script>
@endsection

@section('style')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
