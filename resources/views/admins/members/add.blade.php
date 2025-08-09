@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Member</h1>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <div class="p-5">
                        <form class="member" action="{{ route('member.create') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 col-md-6">
                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">NIK</span>
                                        <input type="text" name="NIK_Member" class="form-control form-control-member @error('NIK_Member') is-invalid @enderror" value="{{ old('NIK_Member') }}">
                                        @error('NIK_Member')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group mb-3">
                                        <span style="font-size: small;">Name</span>
                                        <input type="text" name="Name_Member" class="form-control form-control-member @error('Name_Member') is-invalid @enderror" value="{{ old('Name_Member') }}">
                                        @error('Name_Member')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>                                   
                                </div>
                            </div>
                            <br>
                            <button type="submit" class="btn btn-info btn-member" style="padding-left: 50px; padding-right: 50px;">
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
@endsection