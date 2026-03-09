@extends('layouts.main')
@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <h1 class="h3 mb-2 text-gray-800">Rack Tractor Type</h1>
        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <div class="p-5">
                            <form class="user" action="{{ route('rack.type.update', ['Id_Rack' => $rack->Id_Rack]) }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('put')
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group mb-3">
                                            <span style="font-size: small;">Rack Code</span>
                                            <input type="text" id="Code_Rack" name="Code_Rack"
                                                class="form-control form-control-user" value="{{ $rack->Code_Rack }}"
                                                readonly>
                                        </div>
                                        <div class="form-group mb-3">
                                            <span style="font-size: small;">Type Tractor</span>
                                            <input type="text" name="Type_Tractor_Rack"
                                                class="form-control form-control-user @error('Type_Tractor_Rack') is-invalid @enderror"
                                                value="{{ $rack->Type_Tractor_Rack }}">
                                            @error('Type_Tractor_Rack')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <button type="submit" class="btn btn-info btn-user"
                                    style="padding-left: 50px; padding-right: 50px;">
                                    Save
                                </button>
                                <a href="{{ route('rack.type') }}" class="btn btn-secondary btn-user"
                                    style="padding-left: 50px; padding-right: 50px;">
                                    Cancel
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