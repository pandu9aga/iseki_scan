@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Rack</h1>
    
    @if(session('success'))
        <p style="color:green;">{{ session('success') }}</p>
    @endif

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <div class="p-5">
                        <form class="user" action="{{ route('rack.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 col-md-6">
                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">File Excel</span>
                                        <input type="file"  name="excel" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info" style="padding-left: 50px; padding-right: 50px;">
                                Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->
@endsection