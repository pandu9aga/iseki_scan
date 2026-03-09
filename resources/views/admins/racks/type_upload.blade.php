@extends('layouts.main')
@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <h1 class="h3 mb-2 text-gray-800">Upload Rack Tractor Type</h1>

        @if(session('success'))
            <p style="color:green;">{{ session('success') }}</p>
        @endif

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Instructions</h6>
            </div>
            <div class="card-body">
                <p>Upload an Excel file with the following columns:</p>
                <ul>
                    <li><strong>Column A:</strong> Rack Code</li>
                    <li><strong>Column B:</strong> Type Tractor</li>
                </ul>
                <p>The first row is expected to be a header and will be skipped.</p>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <div class="p-5">
                            <form class="user" action="{{ route('rack.type.import') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group mb-3">
                                            <span style="font-size: small;">File Excel</span>
                                            <input type="file" name="excel" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-info" style="padding-left: 50px; padding-right: 50px;">
                                    Upload
                                </button>
                                <a href="{{ route('rack.type') }}" class="btn btn-secondary"
                                    style="padding-left: 50px; padding-right: 50px;">
                                    Back
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.container-fluid -->
@endsection