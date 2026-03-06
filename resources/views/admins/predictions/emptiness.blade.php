@extends('layouts.main')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Rack Emptiness Prediction</h1>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Predicted Empty Racks</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="predictionTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Rack Code</th>
                                <th>Item Code</th>
                                <th>Avg Interval (H)</th>
                                <th>Total Req</th>
                                <th>Fill (7d)</th>
                                <th>Req (7d)</th>
                                <th>Last Req (H)</th>
                                <th>Probability</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($racks as $rack)
                                <tr class="rack-row" data-interval="{{ $rack->avg_request_interval_h ?? 0 }}"
                                    data-lifetime="{{ $rack->total_req_lifetime ?? 0 }}"
                                    data-fill="{{ $rack->fill_count_7d ?? 0 }}" data-req="{{ $rack->request_count_7d ?? 0 }}"
                                    data-last="{{ $rack->hours_since_last_req ?? 0 }}">
                                    <td>{{ $rack->Code_Rack }}</td>
                                    <td>{{ $rack->Code_Item_Rack }}</td>
                                    <td>{{ number_format($rack->avg_request_interval_h, 1) }}h</td>
                                    <td>{{ $rack->total_req_lifetime }}</td>
                                    <td>{{ $rack->fill_count_7d }}</td>
                                    <td>{{ $rack->request_count_7d }}</td>
                                    <td>{{ $rack->hours_since_last_req }}h ago</td>
                                    <td class="prob-cell">-</td>
                                    <td class="status-cell">
                                        <span class="badge badge-secondary">Processing...</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{asset('js/tf.min.js')}}"></script>
        <script>
            async function loadModel() {
                try {
                    // Updated model path for habit-based model
                    const model = await tf.loadLayersModel('{{ asset("tfjs_model/emptiness/model.json") }}');
                    console.log("Habit Model loaded successfully");
                    predictAll(model);
                } catch (e) {
                    console.error("Failed to load model", e);
                    document.querySelectorAll('.status-cell').forEach(cell => {
                        cell.innerHTML = '<span class="badge badge-danger">Model Error</span>';
                    });
                }
            }

            async function predictAll(model) {
                const rows = document.querySelectorAll('.rack-row');

                for (const row of rows) {
                    const interval = parseFloat(row.dataset.interval);
                    const lifetime = parseFloat(row.dataset.lifetime);
                    const fill = parseFloat(row.dataset.fill);
                    const req = parseFloat(row.dataset.req);
                    const last = parseFloat(row.dataset.last);

                    // Input tensor matching the 5 features from training:
                    // [avg_interval, lifetime, fill_7d, req_7d, hours_since_last]
                    const inputTensor = tf.tensor2d([[interval, lifetime, fill, req, last]]);
                    const prediction = model.predict(inputTensor);
                    const probability = (await prediction.data())[0];

                    const probCell = row.querySelector('.prob-cell');
                    const statusCell = row.querySelector('.status-cell');

                    probCell.innerText = (probability * 100).toFixed(2) + '%';

                    if (probability > 0.8) {
                        statusCell.innerHTML = '<span class="badge badge-danger">High Empty Risk</span>';
                        row.classList.add('table-danger');
                    } else if (probability > 0.5) {
                        statusCell.innerHTML = '<span class="badge badge-warning">Medium Risk</span>';
                        row.classList.add('table-warning');
                    } else {
                        statusCell.innerHTML = '<span class="badge badge-success">Safe</span>';
                        row.classList.add('table-success');
                    }

                    inputTensor.dispose();
                    prediction.dispose();
                }
            }

            document.addEventListener('DOMContentLoaded', loadModel);
        </script>
    @endpush
@endsection