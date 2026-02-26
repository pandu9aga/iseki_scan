@extends('layouts.user')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Achievement</h1>
            <div>
                <form action="{{ route('user_achievement') }}" method="GET" class="form-inline d-inline">
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
                                    <th rowspan="2" class="align-middle text-center sticky-col"
                                        style="background-color: #f8f9fc;">Date
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
                                    <td class="text-center italic sticky-col"><b>TOTAL</b></td>
                                    @foreach($requestsData as $userId => $data)
                                        <td class="text-center">{{ $data['total'] }}</td>
                                    @endforeach
                                    @foreach($recordsData as $userId => $data)
                                        <td class="text-center">{{ $data['total'] }}</td>
                                    @endforeach
                                </tr>
                                <!-- Daily Rows -->
                                @for($i = 1; $i <= $daysInMonth; $i++)
                                    <tr class="hover-row">
                                        <td class="text-center sticky-col"><b>{{ $i }}</b></td>
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

        .sticky-total td.sticky-col {
            z-index: 12 !important;
            left: 0;
            background-color: #e3e6f0 !important;
        }

        .sticky-col {
            position: sticky;
            left: 0;
            background-color: #f8f9fc !important;
            z-index: 5;
            border-right: 2px solid #e3e6f0 !important;
        }

        thead tr:nth-child(1) th.sticky-col {
            z-index: 15;
        }

        thead tr:nth-child(2) th.sticky-col {
            z-index: 14;
        }

        tbody tr:not(.sticky-total):hover td {
            background-image: linear-gradient(0deg, rgba(0, 0, 0, 0.05) 0%, rgba(0, 0, 0, 0.05) 100%);
        }

        tbody tr:not(.sticky-total):hover td.sticky-col {
            background-image: linear-gradient(0deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.1) 100%);
        }
    </style>
@endsection