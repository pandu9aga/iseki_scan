@extends('layouts.main')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Automated Error Prediction</h1>
            <form action="{{ route('prediction.error') }}" method="GET" class="form-inline">
                <label for="date" class="mr-2">Prediction Date:</label>
                <input type="date" name="date" id="date" class="form-control mr-2" value="{{ $date }}">
                <button type="submit" class="btn btn-primary">Refresh Data</button>
            </form>
        </div>

        <div class="row">
            <!-- Summary Cards -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Requests Today</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ count($requests) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Predicted High Risk</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="highRiskCount">Calculating...</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 font-weight-bold text-primary">Error Predictions List for
                    {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="predictionTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th>Member</th>
                                <th>Qty</th>
                                <th>Urgency</th>
                                <th>Risk Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dataset as $item)
                                <tr data-request-id="{{ $item['Id_Request'] }}" data-features="{{ json_encode($item) }}">
                                    <td>{{ $item['Part_Code_Raw'] }} <br><small
                                            class="text-muted">{{ $item['Part_Name'] }}</small></td>
                                    <td>{{ $item['Member_Name'] }}</td>
                                    <td>{{ $item['Requested_Quantity'] }}</td>
                                    <td class="text-center">
                                        @if($item['Is_Urgent'])
                                            <span class="badge badge-warning">Urgent</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="prediction-value">Calculating...</td>
                                    <td class="prediction-status">Loading Model...</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- TensorFlow.js CDN -->
    <script src="{{asset('js/tf.min.js')}}"></script>

    <script>
        let model;
        let highRiskCount = 0;
        const areas = {!! json_encode($areas) !!}; // 35 categories

        async function loadModel() {
            try {
                // Fix for Keras 3 model.json compatibility handled by backend repair
                model = await tf.loadLayersModel('{{ asset("tfjs_model/model.json") }}');
                console.log('Model loaded successfully');
                startPrediction();
            } catch (error) {
                console.error('Error loading TF model:', error);
                $('.prediction-status').text('Model Error').addClass('text-danger');
            }
        }

        async function startPrediction() {
            const rows = document.querySelectorAll('#predictionTable tbody tr');
            highRiskCount = 0;

            for (let row of rows) {
                const data = JSON.parse(row.getAttribute('data-features'));

                // Build 40-feature vector
                // 1-6: Numeric features
                let features = [
                    parseFloat(data.Day_Of_Week),
                    parseFloat(data.Hour_Of_Day),
                    parseFloat(data.Member_ID),
                    parseFloat(data.Requested_Quantity),
                    parseFloat(data.Is_Urgent),
                    parseFloat(data.Was_Delayed_DST)
                ];

                // 7-40: One-Hot Encoding for Area (drop_first=true, so skip index 0)
                const currentArea = data.Area;
                for (let i = 1; i < areas.length; i++) {
                    features.push(areas[i] === currentArea ? 1.0 : 0.0);
                }

                // Verify length is 40
                if (features.length !== 40) {
                    console.warn(`Feature length mismatch: expected 40, got ${features.length}`);
                }

                // Konversi ke Tensor
                const inputTensor = tf.tensor2d([features]);

                // Predict
                const prediction = model.predict(inputTensor);
                const score = (await prediction.data())[0];

                // Update UI
                const scorePercent = (score * 100).toFixed(2) + '%';
                row.querySelector('.prediction-value').innerText = scorePercent;

                const statusCell = row.querySelector('.prediction-status');
                if (score > 0.7) {
                    statusCell.innerHTML = '<span class="badge badge-danger">High Risk</span>';
                    highRiskCount++;
                } else if (score > 0.3) {
                    statusCell.innerHTML = '<span class="badge badge-warning">Medium Risk</span>';
                } else {
                    statusCell.innerHTML = '<span class="badge badge-success">Low Risk</span>';
                }

                // Cleanup tensor
                inputTensor.dispose();
                prediction.dispose();
            }

            document.getElementById('highRiskCount').innerText = highRiskCount + ' requests';
        }

        $(document).ready(function () {
            loadModel();
        });
    </script>
@endsection