@extends('layouts.user')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800" id="top">Record</h1>

    <div id="reader_item" class="mx-auto" style="max-width: 300px;"></div>
    <div id="reader_rack" class="mx-auto" style="max-width: 300px;"></div>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <div class="p-5">
                        <form class="user text-center" action="{{ route('record.create') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 col-md-6 text-center">
                                    <div class="form-group mb-3">
                                        <div>
                                            <span style="font-size: small;">QR Code Item</span>
                                            <div id="parent_qrcode" class="container-fluid d-flex justify-content-start p-0" style="max-width: 150px;">
                                                
                                                <div id="qrcode_item"></div>
                                            </div>
                                            <br>
                                            <a href="#top">
                                                <button type="button" id="scanItem" class="btn btn-warning btn-sm">
                                                    Scan
                                                </button>
                                            </a>
                                        </div>
                                        <span style="font-size: small;">Item Code</span>
                                        <input type="text" name="Code_Item" id="Code_Item" class="form-control form-control-user @error('Code_Item') is-invalid @enderror" value="{{ old('Code_Item') }}">
                                        @error('Code_Item')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 text-center">
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
                                        @error('Code_Rack')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <input type="hidden" id="Correctness" name="Correctness" value="">
                            </div>
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
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script> --}}

<!-- QR Code Generation Script -->
<script>
    var element = document.getElementById('parent_qrcode');
    var width = element.offsetWidth;

    let itemScanner = new Html5QrcodeScanner(
        "reader_item", {
            fps: 10,
            qrbox: {
                width: width,
                height: width,
            },
        }
    );

    let rackScanner = new Html5QrcodeScanner(
        "reader_rack", {
            fps: 10,
            qrbox: {
                width: width,
                height: width,
            },
        }
    );

    var qrcode_item = new QRCode("qrcode_item", {
        width: width,
        height: width
    });

    var qrcode_rack = new QRCode("qrcode_rack", {
        width: width,
        height: width
    });

    function onScanSuccessItem(decodedText, decodedResult) {    
        // Bagi dengan '|', ambil index ke-0
        let parts = decodedText.split('|');
        let itemCode = parts[0];

        document.getElementById("Code_Item").value = itemCode;
        itemScanner.clear();
        makeCodeItem();
        checkCorrectness();
    }

    document.getElementById("scanItem").addEventListener("click", function () {
        let imgElement = document.querySelector("#qrcode_item img");
        if (imgElement) {
            imgElement.src = "";
        }
        itemScanner.render(onScanSuccessItem);
    });

    function makeCodeItem () {    
        var itemText = document.getElementById("Code_Item");
        qrcode_item.makeCode(itemText.value);
    }

    $("#Code_Item").
    on("blur", function () {
        makeCodeItem();
    });

    function onScanSuccessRack(decodedText, decodedResult) {        
        document.getElementById("Code_Rack").value = decodedText;
        rackScanner.clear();
        makeCodeRack ();
        checkCorrectness();
    }

    document.getElementById("scanRack").addEventListener("click", function () {
        let imgElement = document.querySelector("#qrcode_rack img");
        if (imgElement) {
            imgElement.src = "";
        }
        rackScanner.render(onScanSuccessRack);
    });

    function makeCodeRack () {    
        var rackText = document.getElementById("Code_Rack");
        qrcode_rack.makeCode(rackText.value);
    }

    $("#Code_Rack").
    on("blur", function () {
        makeCodeRack();
    });

    function checkCorrectness() {
        let itemValue = $("#Code_Item").val().trim();
        let rackValue = $("#Code_Rack").val().trim();
        let statusCode = $("#status_code");

        // Hilangkan semua tanda baca dan spasi
        itemValue = itemValue.replace(/[^\w]/g, '');

        if (itemValue === "" || rackValue === "") {
            statusCode.html("").removeClass("bg-gradient-success bg-gradient-danger text-white");
            return;
        }

        // AJAX request ke server
        $.get('./record/check', {
            Code_Rack: rackValue,
            Code_Item: itemValue
        }, function(response) {
            if (response.status === "correct") {
                statusCode.html("Correct").removeClass("bg-gradient-danger").addClass("text-white bg-gradient-success");
                document.getElementById("Correctness").value = 1;
            } else {
                statusCode.html("Incorrect").removeClass("bg-gradient-success").addClass("text-white bg-gradient-danger");
                document.getElementById("Correctness").value = 2;
            }
        });
    }

    $("#Code_Item, #Code_Rack").on("blur", checkCorrectness);
</script>
@endsection

@section('bujukan')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bujukan Tok</title>
</head>
<body>
    <div class="row">
        Data tables inquiry data
    </div>
    <div class="when-you-go would-you-even-turn-to-say i-dont-love-you like-i-did yesterday">
        <div id="sometimes_i_cry_so_hard_from_pleading">
            <a href="what_the_worst_that _i_can_say">Things are better if I stay</a>
            <button type="button">
                So long and Good Night
            </button>
            <button type="button">
                So long and Good Night
            </button>
        </div>
    </div>
</body>
</html>
@endsection

@section('testing')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Testing</title>
</head>
<body>
    <div class="row">
        Testing
    </div>
</body>
</html>
@endsection