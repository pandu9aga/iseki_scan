@extends('layouts.mc')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Achievement</h1>
            <div>
                <form action="{{ route('mc_achievement') }}" method="GET" class="form-inline d-inline">
                    <input type="month" name="month" class="form-control mr-2" value="{{ $month }}">
                    <button type="submit" class="btn btn-primary mr-2">Submit</button>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Achievement Report -
                    {{ \Carbon\Carbon::parse($month)->format('F Y') }}
                </h6>
            </div>
            <div class="card-body">
                @if(count($requestsData) == 0)
                    <div class="alert alert-info">
                        No active members found for this period.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered" id="achievementTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="align-middle text-center" style="background-color: #f8f9fc;">Date
                                    </th>
                                    <th colspan="{{ count($requestsData) }}" class="text-center bg-primary text-white">REQUESTS
                                    </th>
                                    <th colspan="{{ count($recordsData) }}" class="text-center bg-success text-white">RECORDS
                                    </th>
                                </tr>
                                <tr>
                                    <!-- User names for Requests -->
                                    @foreach($requestsData as $userId => $data)
                                        <th class="text-center small">{{ $data['name'] }}</th>
                                    @endforeach
                                    <!-- User names for Records -->
                                    @foreach($recordsData as $userId => $data)
                                        <th class="text-center small">{{ $data['name'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Total Row -->
                                <tr class="sticky-total" style="background-color: #e3e6f0; font-weight: bold;">
                                    <td class="text-center italic"><b>TOTAL</b></td>
                                    @foreach($requestsData as $userId => $data)
                                        <td class="text-center">{{ $data['total'] }}</td>
                                    @endforeach
                                    @foreach($recordsData as $userId => $data)
                                        <td class="text-center">{{ $data['total'] }}</td>
                                    @endforeach
                                </tr>
                                <!-- Daily Rows -->
                                @for($i = 1; $i <= $daysInMonth; $i++)
                                    <tr>
                                        <td class="text-center"><b>{{ $i }}</b></td>
                                        <!-- Days for Requests -->
                                        @foreach($requestsData as $userId => $data)
                                            <td class="text-center">{{ $data['days'][$i] }}</td>
                                        @endforeach
                                        <!-- Days for Records -->
                                        @foreach($recordsData as $userId => $data)
                                            <td class="text-center">{{ $data['days'][$i] }}</td>
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        #achievementTable th,
        #achievementTable td {
            vertical-align: middle;
            padding: 0.5rem;
        }

        .table-responsive {
            max-height: 700px;
            overflow-y: auto;
        }

        thead tr:nth-child(1) th {
            position: sticky;
            top: 0;
            z-index: 11;
            background-color: #f8f9fc;
        }

        thead tr:nth-child(2) th {
            position: sticky;
            top: 41px;
            /* Height of the first row */
            z-index: 10;
            background-color: #f8f9fc;
        }

        .sticky-total td {
            position: sticky;
            top: 95px;
            /* Height of Row 1 + Row 2. Adjusted to stay below header */
            z-index: 9;
            background-color: #e3e6f0 !important;
        }
    </style>
@endsection