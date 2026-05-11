@extends('layouts.main')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">{{ $titlePrefix }}</h1>
            <a href="{{ route('mistake') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Report
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-light">
                <h6 class="m-0 font-weight-bold text-primary">Mistake Records - {{ ucfirst($category) }}</h6>
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
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mistakes as $index => $m)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $m->Day_Mistake }}</td>
                                    <td class="font-weight-bold text-danger">{{ $m->PIC }}</td>
                                    <td>{{ $m->Is_Withdrawal ? ($m->urgent ? $m->urgent->Code_Rack : '-') : ($m->request ? $m->request->Code_Rack : '-') }}</td>
                                    <td>{{ $m->Is_Withdrawal ? '-' : (($m->request && $m->request->rack) ? $m->request->rack->Name_Item_Rack : '-') }}</td>
                                    <td>{{ $m->Is_Withdrawal ? '-' : (($m->request && $m->request->member) ? $m->request->display_name : '-') }}</td>
                                    <td>{{ $m->Is_Withdrawal ? '-' : (($m->request && $m->request->record && $m->request->record->member) ? $m->request->record->display_name : '-') }}</td>
                                    <td>{{ $m->Is_Withdrawal ? ($m->withdrawal ? $m->withdrawal->Date_Withdrawal : '-') : ($m->request ? $m->request->Time_Request : '-') }}</td>
                                    <td>{{ $m->Is_Withdrawal ? ($m->withdrawal ? $m->withdrawal->Date_Finish_Receiving : '-') : (($m->request && $m->request->record) ? $m->request->record->Time_Record : '-') }}</td>
                                    <td>
                                        @if($m->Category_Mistake == 'lain-lain')
                                            {{ $m->Manual_Category_Detail }}
                                        @else
                                            {{ ucfirst($m->Category_Mistake) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            @if($mistakes->count() == 0)
                                <tr>
                                    <td colspan="10" class="text-center">No records found.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection