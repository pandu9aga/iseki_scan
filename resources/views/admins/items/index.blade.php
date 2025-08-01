@extends('layouts.main')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Item</h1>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>No</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ( $item as $i )
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $i->Code_Item }}</td>
                            <td>{{ $i->Name_Item }}</td>
                            <td>
                                <a href="{{ route('item.edit', $i->Id_Item) }}" class="btn btn-sm btn-warning">edit</a>
                                {{-- <a href="{{ route('item.destroy', $i->Id_Item) }}" class="btn btn-sm btn-danger">delete</a> --}}
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#exampleModal-{{$i->Id_Item}}">
                                    delete
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <a href="{{ route('item.add') }}" class="d-sm-inline-block btn btn-md btn-success shadow-sm">
            <span style="padding-left: 50px; padding-right: 50px;">Add</span>
        </a>
    </div>
</div>
<!-- /.container-fluid -->

@foreach ( $item as $i )
<!-- Modal -->
<div class="modal fade" id="exampleModal-{{$i->Id_Item}}" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title fs-5" id="exampleModalLabel">Delete Item</h4>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                Apakah anda yakin akan menghapus data - <b>{{$i->Name_Item}}</b>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeModal('exampleModal-{{$i->Id_Item}}')">Close</button>
                <form action="{{ route('item.destroy', $i->Id_Item) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

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