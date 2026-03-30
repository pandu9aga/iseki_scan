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
                <div class="card-body">
                    <form action="{{ route('area.scan.process') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="Code_Rack">Scan Rack Code (Barcode/QR)</label>
                            <input type="text" class="form-control form-control-user" id="Code_Rack" name="Code_Rack" placeholder="Scan here..." autofocus required>
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
<script>
    // Autofocus on load and rescan after process
    $(document).ready(function() {
        $('#Code_Rack').focus();
    });
</script>
@endsection
