@extends('layouts.main')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">{{ $titlePrefix }}</h1>
            <a href="{{ route('forgot') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Report
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light">
                <h6 class="m-0 font-weight-bold text-primary">Forgot Records</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Date</th>
                                <th>PIC (Recorded)</th>
                                <th>Rack</th>
                                <th>Item Name</th>
                                <th>Requester</th>
                                <th>Record PIC</th>
                                <th>Request Time</th>
                                <th>Record Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($forgots as $index => $f)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $f->Day_Forgot }}</td>
                                    <td class="font-weight-bold text-danger">{{ $f->PIC }}</td>
                                    <td>{{ $f->request ? $f->request->Code_Rack : '-' }}</td>
                                    <td>{{ ($f->request && $f->request->rack) ? $f->request->rack->Name_Item_Rack : '-' }}</td>
                                    <td>{{ ($f->request && $f->request->member) ? $f->request->display_name : '-' }}</td>
                                    <td>{{ ($f->request && $f->request->record && $f->request->record->member) ? $f->request->record->display_name : '-' }}
                                    </td>
                                    <td>{{ $f->request ? $f->request->Time_Request : '-' }}</td>
                                    <td>{{ ($f->request && $f->request->record) ? $f->request->record->Time_Record : '-' }}</td>
                                </tr>
                            @endforeach

                            @if($forgots->count() == 0)
                                <tr>
                                    <td colspan="9" class="text-center">No records found.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection