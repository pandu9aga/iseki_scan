@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Item</h1>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <div class="p-5">
                        <form class="user" action="{{ route('item.update', ['Id_Item' => $Id_Item->Id_Item]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('put')
                            <div class="row">
                                <div class="col-lg-6 col-md-6">
                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">Item Name</span>
                                        <input type="text" name="Name_Item" class="form-control form-control-user @error('Name_Item') is-invalid @enderror" value="{{ $Id_Item->Name_Item }}">
                                        @error('Name_Item')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">Item Code</span>
                                        <input type="text" id="Code_Item" name="Code_Item" class="form-control form-control-user @error('Code_Item') is-invalid @enderror" value="{{ $Id_Item->Code_Item }}">
                                        @error('Code_Item')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 text-center">
                                    <div>
                                        <span style="font-size: small;">QR Code</span>
                                        <div id="parent_qrcode" class="container-fluid d-flex justify-content-start p-0" style="max-width: 150px;">
                                            <div id="qrcode"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <button type="submit" class="btn btn-info btn-user" style="padding-left: 50px; padding-right: 50px;">
                                Save
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<!-- QR Code Library -->
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/qrcode.min.js') }}"></script>
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script> --}}

<!-- QR Code Generation Script -->
<script>
    var element = document.getElementById('parent_qrcode');
    var width = element.offsetWidth;

    var qrcode = new QRCode("qrcode", {
        width: width,
        height: width
    });

    function makeCode () {    
        var elText = document.getElementById("Code_Item");
        qrcode.makeCode(elText.value);
    }

    makeCode();
    
    $("#Code_Item").
    on("blur", function () {
        makeCode();
    });
</script>
@endsection