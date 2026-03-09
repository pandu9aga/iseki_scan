@extends('layouts.main')
@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <h1 class="h3 mb-2 text-gray-800">Rack</h1>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <a href="{{ route('rack.type.upload') }}" class="d-sm-inline-block btn btn-md btn-success shadow-sm">
                <span style="padding-left: 50px; padding-right: 50px;"><i class="fas fa-fw fa-upload"></i> <i
                        class="fas fa-fw fa-table"></i> Import</span>
            </a>
            <a href="{{ route('rack.type.export') }}" class="d-sm-inline-block btn btn-md btn-primary shadow-sm">
                <span style="padding-left: 50px; padding-right: 50px;"><i class="fas fa-fw fa-download"></i> <i
                        class="fas fa-fw fa-table"></i> Export</span>
            </a>
            <a href="{{ route('rack') }}" class="d-sm-inline-block btn btn-md btn-secondary shadow-sm">
                <span style="padding-left: 50px; padding-right: 50px;"><i class="fas fa-fw fa-table"></i> Rack</span>
            </a>
        </div>
        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Rack Code</th>
                                <th>Type Tractor</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>No</th>
                                <th>Rack Code</th>
                                <th>Type Tractor</th>
                                <th>Action</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            @foreach ($rack as $i)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $i->Code_Rack }}</td>
                                    <td>{{ $i->Type_Tractor_Rack }}</td>
                                    <td>
                                        <a href="{{ route('rack.type.edit', $i->Id_Rack) }}"
                                            class="btn btn-sm btn-warning">edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Page Heading -->
    </div>
    <!-- /.container-fluid -->

@endsection

@section('style')
    <!-- Custom styles for this page -->
    <link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
@endsection

@section('script')
    <!-- Page level plugins -->
    <script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>

    <!-- Page level custom scripts -->
    <script src="{{asset('js/demo/datatables-demo.js')}}"></script>

    <script>
        $('#dataTable').on('click', '[data-bs-toggle="modal"]', function () {
            var target = $(this).data('bs-target');
            var modal = new bootstrap.Modal(document.getElementById(target.substring(1)), {
                backdrop: true,
                keyboard: true
            });
            modal.show();
        });
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        }
    </script>
@endsection