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
                        <form class="user text-center" action="{{ route('record.create') }}" id="recordForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 col-md-6 text-center">
                                    <div class="form-group mb-3">
                                        <div>
                                            {{-- <span style="font-size: small;">QR Code Item</span>
                                            <div id="parent_qrcode" class="container-fluid d-flex justify-content-start p-0" style="max-width: 150px;">
                                                <div id="qrcode_item"></div>
                                            </div>
                                            <br> --}}
                                            <a href="#top">
                                                <button type="button" id="scanItem" class="btn btn-warning btn-sm">
                                                    Scan
                                                </button>
                                            </a>
                                        </div>
                                        <span style="font-size: small;">Item Code</span>
                                        <input type="text" name="Code_Item" id="Code_Item" class="form-control form-control-user @error('Code_Item') is-invalid @enderror" value="{{ old('Code_Item') }}" required>
                                        @error('Code_Item')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 text-center">
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
                                        @error('Code_Rack')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-lg-12 text-center">
                                    <div class="form-group mb-3">
                                        <label for="Sum_Record" style="font-size: small;">Sum Record</label>
                                        <input type="number" name="Sum_Record" id="Sum_Record" class="form-control form-control-user @error('Sum_Record') is-invalid @enderror" value="{{ old('Sum_Record', 1) }}" min="1" step="1" required>
                                        @error('Sum_Record')
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
                                    {{-- <button type="submit" class="btn btn-info btn-user" style="padding-left: 50px; padding-right: 50px;">
                                        Save
                                    </button> --}}
                                    <button type="button" id="saveBtn" class="btn btn-info btn-user" 
                                            style="padding-left: 50px; padding-right: 50px;">
                                        Save
                                    </button>
                                </div>
                                <div class="col-lg-3 col-md-3 text-center"></div>
                            </div>
                        </form>
                        <!-- Modal Pilih Area -->
                        <div class="modal fade" id="areaModal" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Pilih Area Supply</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="areaOptions" class="list-group"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    // var element = document.getElementById('parent_qrcode');
    // var width = element.offsetWidth;
    var width = 150;

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

    // var qrcode_item = new QRCode("qrcode_item", {
    //     width: width,
    //     height: width
    // });

    // var qrcode_rack = new QRCode("qrcode_rack", {
    //     width: width,
    //     height: width
    // });

    function onScanSuccessItem(decodedText, decodedResult) {    
        // Bagi dengan '|', ambil index ke-0
        let parts = decodedText.split('|');
        let itemCode = parts[0];

        document.getElementById("Code_Item").value = itemCode;
        itemScanner.clear();
        // makeCodeItem();
        checkCorrectness();
    }

    document.getElementById("scanItem").addEventListener("click", function () {
        // let imgElement = document.querySelector("#qrcode_item img");
        // if (imgElement) {
        //     imgElement.src = "";
        // }
        itemScanner.render(onScanSuccessItem);
    });

    // function makeCodeItem () {    
    //     var itemText = document.getElementById("Code_Item");
    //     qrcode_item.makeCode(itemText.value);
    // }

    // $("#Code_Item").
    // on("blur", function () {
    //     makeCodeItem();
    // });

    function onScanSuccessRack(decodedText, decodedResult) {        
        document.getElementById("Code_Rack").value = decodedText;
        rackScanner.clear();
        // makeCodeRack ();
        checkCorrectness();
    }

    document.getElementById("scanRack").addEventListener("click", function () {
        // let imgElement = document.querySelector("#qrcode_rack img");
        // if (imgElement) {
        //     imgElement.src = "";
        // }
        rackScanner.render(onScanSuccessRack);
    });

    // function makeCodeRack () {    
    //     var rackText = document.getElementById("Code_Rack");
    //     qrcode_rack.makeCode(rackText.value);
    // }

    // $("#Code_Rack").
    // on("blur", function () {
    //     makeCodeRack();
    // });

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

    $("#saveBtn").on("click", function () {
        let form = $("#recordForm");
        let codeItem = $("#Code_Item").val();
        let codeRack = $("#Code_Rack").val();

        if (!codeItem || !codeRack) {
            alert("Isi dulu Item Code dan Rack Code.");
            return;
        }

        $.post("{{ route('record.checkMultiple') }}", {
            _token: "{{ csrf_token() }}",
            Code_Item: codeItem
        }, function (response) {
            if (response.count === 0) {
                // tidak ada request matching -> langsung submit
                form.submit();
            } else if (response.count === 1) {
                // hanya 1 -> langsung submit
                form.append(`<input type="hidden" name="Id_Request" value="${response.requests[0].id}">`);
                form.submit();
            } else {
                // lebih dari 1 -> tampilkan modal
                let areaOptions = $("#areaOptions");
                areaOptions.empty();
                response.requests.forEach(r => {
                    areaOptions.append(`
                        <button type="button" class="list-group-item list-group-item-action"
                            onclick="selectArea('${r.id}')">
                            ${r.area}
                        </button>
                    `);
                });
                $("#areaModal").modal("show");
            }
        });
    });

    function selectArea(idRequest) {
        $("#recordForm").append(`<input type="hidden" name="Id_Request" value="${idRequest}">`);
        $("#areaModal").modal("hide");
        $("#recordForm").submit();
    }

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