{{-- resources/views/users/label/index.blade.php --}}
@extends('layouts.user')

@section('content')
    <div class="container-fluid">
        {{-- Info Card --}}
        <div class="alert alert-info mb-4">
            <h5 class="mb-2"><strong>Tata Cara Penggunaan Request Label</strong></h5>
            <ol class="mb-2">
                <li>Isi <strong>Nama Label</strong> sesuai kebutuhan label yang ingin dicetak.</li>
                <li>Masukkan <strong>Quantity</strong> (jumlah label yang ingin dicetak).</li>
                <li>Pilih <strong>Area</strong> sesuai lokasi atau area label akan digunakan.</li>
                <li>Pilih <strong>Jenis</strong> label: <em>Rack Kecil</em>, <em>Pallet</em>, atau <em>Rack Normal</em>.</li>
                <li>Klik tombol <strong>Request Label</strong> untuk menambahkan ke queue.</li>
                <li>Klik tombol <strong>Print Now</strong> untuk mencetak semua label di queue.</li>
            </ol>
            <div class="mt-2">
                <strong>Catatan:</strong> Untuk melakukan <strong>print label</strong>, silakan kunjungi aplikasi
                <a href="{{ $urlLabel }}" target="_blank"><strong>Iseki Label</strong></a>.
            </div>
        </div>

        <h1 class="h3 mb-2 text-gray-800">Request Label</h1>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        {{-- Request Form --}}
        <div class="card shadow mb-4">
            <div class="card-body">
                <form action="{{ route('member.label.store') }}" method="POST" id="labelRequestForm">
                    @csrf
                    <div class="row">
                        {{-- Label Name / Rack No --}}
                        <div class="col-lg-6 col-md-6">
                            <div class="form-group">
                                <label for="Label_Name">Nama Label / Rack No <span class="text-danger">*</span></label>
                                <div class="autocomplete-wrapper">
                                    <input
                                        type="text"
                                        name="Label_Name"
                                        id="Label_Name"
                                        class="form-control @error('Label_Name') is-invalid @enderror"
                                        value="{{ old('Label_Name') }}"
                                        placeholder="Ketik rack no atau scan barcode..."
                                        autocomplete="off"
                                        required
                                    >
                                    <div id="autocomplete-results" class="autocomplete-results"></div>
                                </div>
                                @error('Label_Name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Quantity --}}
                        <div class="col-lg-6 col-md-6">
                            <div class="form-group">
                                <label for="Quantity">Quantity <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    name="Quantity"
                                    id="Quantity"
                                    class="form-control @error('Quantity') is-invalid @enderror"
                                    value="{{ old('Quantity', 1) }}"
                                    min="1"
                                    max="100"
                                    required
                                >
                                @error('Quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Area --}}
                        <div class="col-lg-6 col-md-6">
                            <div class="form-group">
                                <label for="Area">Area <span class="text-danger">*</span></label>
                                <select name="Area" id="Area" class="form-control @error('Area') is-invalid @enderror" required>
                                    <option value="" disabled {{ old('Area') ? '' : 'selected' }}>-- Pilih Area --</option>
                                    @php
                                        $areas = [
                                            'Normal' => 'Normal',
                                            'Main-1' => 'Main-1',
                                            'AGV' => 'AGV',
                                            'Main-2' => 'Main-2',
                                            'Main' => 'Main',
                                            'Main-3' => 'Main-3',
                                            'LO' => 'LO',
                                            'Main-4' => 'Main-4',
                                            'Sub-0' => 'Sub-0',
                                            'Sub-Front-MK' => 'Sub-Front-MK',
                                            'Sub-1' => 'Sub-1',
                                            'Sub-Front-HST' => 'Sub-Front-HST',
                                            'Sub-2' => 'Sub-2',
                                            'Sub-Arm-MK' => 'Sub-Arm-MK',
                                            'Sub-3' => 'Sub-3',
                                            'Sub-Arm-HST' => 'Sub-Arm-HST',
                                            'Sub-4' => 'Sub-4',
                                            'Sub-Mid-HST' => 'Sub-Mid-HST',
                                            'Sub-5' => 'Sub-5',
                                            'Sub-Gear-MK' => 'Sub-Gear-MK',
                                            'Sub-6' => 'Sub-6',
                                            'Sub-Gear-HST' => 'Sub-Gear-HST',
                                            'Sub-7' => 'Sub-7',
                                            'Sub-Cylinder-1' => 'Sub-Cylinder-1',
                                            'Sub-8' => 'Sub-8',
                                            'Sub-Cylinder-2' => 'Sub-Cylinder-2',
                                            'Sub-9' => 'Sub-9',
                                            'Cucian-Cylinder' => 'Cucian-Cylinder',
                                            'Sub-10' => 'Sub-10',
                                            'Cucian-Houshing' => 'Cucian-Houshing',
                                            'Sub-Houshing' => 'Sub-Houshing',
                                            'SXG-3' => 'SXG-3',
                                            'Painting-A' => 'Painting-A',
                                            'Painting-B' => 'Painting-B',
                                            'Palletina' => 'Palletina',
                                        ];
                                    @endphp
                                    @foreach($areas as $value => $label)
                                        <option value="{{ $value }}" {{ old('Area') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('Area')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Jenis --}}
                        <div class="col-lg-6 col-md-6">
                            <div class="form-group">
                                <label for="Jenis">Jenis <span class="text-danger">*</span></label>
                                <select name="Jenis" id="Jenis" class="form-control @error('Jenis') is-invalid @enderror" required>
                                    <option value="" disabled {{ old('Jenis') ? '' : 'selected' }}>-- Pilih Jenis --</option>
                                    <option value="kecil" {{ old('Jenis') == 'kecil' ? 'selected' : '' }}>Rack Kecil</option>
                                    <option value="pallet" {{ old('Jenis') == 'pallet' ? 'selected' : '' }}>Pallet</option>
                                    <option value="besar" {{ old('Jenis') == 'besar' ? 'selected' : '' }}>Rack Normal</option>
                                </select>
                                @error('Jenis')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Urgent Checkbox --}}
                        <div class="col-lg-6 col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox mt-4">
                                    <input type="checkbox" name="urgent" class="custom-control-input" id="urgent" value="1" {{ old('urgent') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="urgent">
                                        <span class="text-danger font-weight-bold">⚡ Urgent</span>
                                        <small class="text-muted">(Prioritas tinggi)</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Label Preview Row --}}
                    <div class="row" id="labelPreviewWrapper" style="display:none;">
                        <div class="col-12">
                            <div class="label-preview-inner">
                                <div class="label-preview-header">
                                    <span class="label-preview-badge" id="labelPreviewBadge"></span>
                                    <div class="label-preview-caption" id="labelPreviewCaption"></div>
                                </div>
                                <img id="labelPreviewImg" src="" alt="Preview Label" class="label-preview-img">
                            </div>
                        </div>
                    </div>

                    <!-- Request Label di atas form -->
                    <div class="row mt-3">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5 py-3">
                                <i class="fas fa-paper-plane mr-2"></i>Request Label
                            </button>
                        </div>
                    </div>

                    <!-- Print Now di bawah table/queue -->
                    <div class="row mt-5 mb-4">
                        <div class="col-12 text-center">
                            <hr class="my-4">
                            <h5 class="text-muted mb-3">Auto Print Queue</h5>
                            <button type="button" id="printNowBtn" class="btn btn-success btn-lg px-5 py-3" {{ $labels->isEmpty() ? 'disabled' : '' }}>
                                <i class="fas fa-print mr-2"></i>Print All Labels
                                <span class="badge badge-light ml-2" id="queueCount">{{ $labels->count() }}</span>
                            </button>
                            <p class="text-muted small mt-2">Klik untuk mencetak semua label di queue</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Queue Table --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-primary text-white">
                <h6 class="m-0 font-weight-bold">Daftar Queue Label Print</h6>
                <div>
                    <span class="badge badge-light mr-2">{{ $labels->where('urgent', true)->count() }} Urgent</span>
                    <span class="badge badge-info">{{ $labels->count() }} Total Pending</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%">
                        <thead class="thead-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Rack Code</th>
                            <th width="10%">Label Type</th>
                            <th width="8%">Qty</th>
                            <th width="15%">Requested By</th>
                            <th width="12%">Area</th>
                            <th width="15%">Tanggal Request</th>
                            <th width="10%">Status</th>
                            <th width="10%">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($labels as $i => $label)
                            <tr class="{{ $label->urgent ? 'table-danger' : '' }}" data-id="{{ $label->id }}">
                                <td>{{ $i + 1 }}</td>
                                <td class="font-weight-bold">{{ $label->rack_code }}</td>
                                <td>
                                    @php
                                        $typeBadges = [
                                            'kecil' => 'badge-info',
                                            'pallet' => 'badge-warning',
                                            'besar' => 'badge-primary',
                                        ];
                                    @endphp
                                    <span class="badge {{ $typeBadges[$label->label_type] ?? 'badge-secondary' }}">
                                        {{ ucfirst($label->label_type) }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $label->quantity }}</td>
                                <td>{{ $label->requested_by }}</td>
                                <td>{{ $label->area_name }}</td>
                                <td>{{ $label->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                <td class="text-center">
                                    @if($label->urgent)
                                        <span class="badge badge-danger"><i class="fas fa-exclamation mr-1"></i>Urgent</span>
                                    @else
                                        <span class="badge badge-secondary">Normal</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger delete-label" data-id="{{ $label->id }}" title="Hapus dari queue">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('styles')
    <style>
        /* ... autocomplete styles ... */

        /* ============================================
           LABEL PREVIEW THUMBNAIL - FULL WIDTH ROW
           ============================================ */
        #labelPreviewWrapper {
            animation: fadeInUp 0.25s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .label-preview-inner {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-left: 4px solid #4e73df;
            border-radius: 6px;
            padding: 10px 14px;
            width: 100%;
            max-width: 100%; /* Prevent overflow */
            box-sizing: border-box; /* Include padding in width */
        }

        .label-preview-header {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap; /* Wrap on small screens */
        }

        .label-preview-img {
            width: 100%; /* Full width parent */
            max-width: 100%; /* Prevent overflow */
            height: auto; /* Maintain aspect ratio */
            max-height: 150px; /* Limit max height */
            object-fit: contain; /* Fit within container */
            object-position: left center; /* Align left */
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            cursor: zoom-in;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: block;
        }

        /* Type-specific max heights */
        .label-preview-img[data-type="kecil"]  { max-height: 80px; }
        .label-preview-img[data-type="besar"]  { max-height: 120px; }
        .label-preview-img[data-type="pallet"] { max-height: 200px; }

        .label-preview-img:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Enlarged/lightbox state */
        .label-preview-img.enlarged {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(1);
            width: auto;
            height: auto;
            max-height: 80vh;
            max-width: 90vw;
            z-index: 9999;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            cursor: zoom-out;
            border-radius: 8px;
            object-fit: contain;
        }

        .label-preview-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 9998;
            cursor: zoom-out;
        }

        .label-preview-badge {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 3px 8px;
            border-radius: 20px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .label-preview-caption {
            font-size: 0.8rem;
            color: #5a5c69;
            line-height: 1.4;
            word-break: break-word; /* Prevent text overflow */
        }

        .label-preview-caption strong {
            display: block;
            color: #3a3b45;
            font-size: 0.85rem;
        }

        /* ============================================
           MOBILE RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .label-preview-inner {
                padding: 8px 10px;
            }

            .label-preview-img[data-type="kecil"]  { max-height: 60px; }
            .label-preview-img[data-type="besar"]  { max-height: 90px; }
            .label-preview-img[data-type="pallet"] { max-height: 150px; }

            .label-preview-img.enlarged {
                max-width: 95vw;
                max-height: 70vh;
            }
        }

        @media (max-width: 480px) {
            .label-preview-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .label-preview-img[data-type="pallet"] { max-height: 120px; }
        }

        /* ============================================
           BUTTON WRAPPER - JARAK LEBAR
           ============================================ */

        .button-wrapper {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .btn-action {
            min-width: 220px;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        /* SPACER ANTAR BUTTON - JARAK BESAR */
        .button-spacer {
            width: 80px; /* Jarak 80px di desktop */
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Garis pemisah (optional) */
        .button-spacer::before {
            content: "";
            width: 4px;
            height: 40px;
            background: #e3e6f0;
            border-radius: 2px;
        }

        /* Hover effect */
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        /* ============================================
           MOBILE: Jarak tetap lebar tapi vertikal
           ============================================ */
        @media (max-width: 768px) {
            .button-wrapper {
                flex-direction: column;
                gap: 25px; /* Jarak 25px antar button */
            }

            .button-spacer {
                width: auto;
                height: 0;
                display: none; /* Sembunyikan spacer vertikal */
            }

            /* Alternatif: tampilkan garis horizontal */
            .button-spacer.mobile-show {
                display: block;
                width: 60%;
                height: 2px;
                background: #e3e6f0;
                margin: 10px 0;
            }

            .btn-action {
                width: 90%;
                max-width: 320px;
                min-width: unset;
                padding: 1.2rem 2rem;
                font-size: 1.05rem;
            }
        }

        /* ============================================
           SMALL MOBILE
           ============================================ */
        @media (max-width: 480px) {
            .button-wrapper {
                gap: 20px;
            }

            .btn-action {
                width: 95%;
                padding: 1.1rem 1.5rem;
            }
        }
    </style>
@endsection
@section('script')
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#dataTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[6, 'desc']], // Sort by date descending
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_",
                    infoEmpty: "Tidak ada data",
                    emptyTable: '<i class="fas fa-inbox fa-2x mb-2 d-block mt-2"></i>Belum ada data queue label',
                    zeroRecords: "Tidak ada data yang cocok",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                }
            });

            // ── Label Jenis Preview ──────────────────────────────────────────
            const labelMeta = {
                kecil: {
                    img: '{{ asset("img/label-kecil.png") }}',
                    label: 'Rack Kecil',
                    desc: 'Label ukuran kecil untuk rak kompak.',
                    badgeClass: 'badge-info',
                    borderColor: '#36b9cc'
                },
                pallet: {
                    img: '{{ asset("img/label-pallet.png") }}',
                    label: 'Pallet',
                    desc: 'Label pallet untuk penumpukan barang.',
                    badgeClass: 'badge-warning',
                    borderColor: '#f6c23e'
                },
                besar: {
                    img: '{{ asset("img/label-normal.png") }}',
                    label: 'Rack Normal',
                    desc: 'Label ukuran standar untuk rak biasa.',
                    badgeClass: 'badge-primary',
                    borderColor: '#4e73df'
                }
            };

            function updateLabelPreview(val) {
                const $wrapper = $('#labelPreviewWrapper');
                const $inner   = $wrapper.find('.label-preview-inner');
                if (!val || !labelMeta[val]) {
                    $wrapper.hide();
                    return;
                }
                const meta = labelMeta[val];
                $('#labelPreviewImg').attr('src', meta.img).attr('alt', meta.label).attr('data-type', val);
                $('#labelPreviewBadge')
                    .text(meta.label)
                    .attr('class', 'label-preview-badge badge ' + meta.badgeClass);
                $('#labelPreviewCaption').html(
                    '<strong>' + meta.label + '</strong>' + meta.desc
                );
                $inner.css('border-left-color', meta.borderColor);
                $wrapper.show();
            }

            // Init on page load (old value)
            updateLabelPreview($('#Jenis').val());

            $('#Jenis').on('change', function() {
                updateLabelPreview($(this).val());
            });

            // Zoom / lightbox on click
            $('#labelPreviewImg').on('click', function() {
                if ($(this).hasClass('enlarged')) return;
                $('<div class="label-preview-overlay"></div>').appendTo('body');
                $(this).addClass('enlarged');
            });

            $(document).on('click', '.label-preview-overlay', function() {
                $(this).remove();
                $('#labelPreviewImg').removeClass('enlarged');
            });

            // ── /Label Jenis Preview ─────────────────────────────────────────

            // Simple Autocomplete Implementation
            const $input = $('#Label_Name');
            const $results = $('#autocomplete-results');
            let searchTimeout;
            let selectedIndex = -1;
            let results = [];

            $input.on('input', function() {
                const query = $(this).val().trim();

                clearTimeout(searchTimeout);
                selectedIndex = -1;

                if (query.length < 1) {
                    $results.removeClass('show').empty();
                    return;
                }

                searchTimeout = setTimeout(() => search(query), 300);
            });

            $input.on('keydown', function(e) {
                if (!$results.hasClass('show')) return;

                const $items = $results.find('.autocomplete-item');

                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        selectedIndex = Math.min(selectedIndex + 1, $items.length - 1);
                        updateSelection($items);
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        selectedIndex = Math.max(selectedIndex - 1, -1);
                        updateSelection($items);
                        break;
                    case 'Enter':
                        e.preventDefault();
                        if (selectedIndex >= 0) {
                            selectItem(results[selectedIndex]);
                        }
                        break;
                    case 'Escape':
                        $results.removeClass('show');
                        break;
                }
            });

            $(document).on('click', '.autocomplete-item', function() {
                const index = $(this).data('index');
                selectItem(results[index]);
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.autocomplete-wrapper').length) {
                    $results.removeClass('show');
                }
            });

            function search(query) {
                $results.html('<div class="autocomplete-loading"><i class="fas fa-spinner fa-spin mr-2"></i>Mencari...</div>').addClass('show');

                $.ajax({
                    url: '{{ route("member.label.search") }}',
                    data: { query: query },
                    success: function(data) {
                        results = data.slice(0, 10);
                        renderResults();
                    },
                    error: function() {
                        $results.html('<div class="autocomplete-empty text-danger">Gagal memuat data</div>');
                    }
                });
            }

            function renderResults() {
                if (results.length === 0) {
                    $results.html('<div class="autocomplete-empty">Tidak ada hasil</div>');
                    return;
                }

                const html = results.map((item, index) => `
                    <div class="autocomplete-item" data-index="${index}">
                        <div class="autocomplete-main">${escapeHtml(item.rack_no)}</div>
                        <div class="autocomplete-sub">
                            ${escapeHtml(item.item_code || '')} ${item.part_name ? '• ' + escapeHtml(item.part_name) : ''}
                        </div>
                    </div>
                `).join('');

                $results.html(html).addClass('show');
            }

            function updateSelection($items) {
                $items.removeClass('selected');
                if (selectedIndex >= 0) {
                    $items.eq(selectedIndex).addClass('selected').get(0).scrollIntoView({ block: 'nearest' });
                }
            }

            function selectItem(item) {
                if (!item) return;

                $input.val(item.rack_no.toUpperCase().replace(/\s/g, ''));
                $results.removeClass('show').empty();

                // Auto focus to quantity
                $('#Quantity').focus().select();
            }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Form validation for Request Label
            $('#labelRequestForm').on('submit', function(e) {
                const labelName = $('#Label_Name').val().trim();
                if (!labelName) {
                    e.preventDefault();
                    alert('Nama Label wajib diisi!');
                    $('#Label_Name').focus();
                    return false;
                }

                // Disable submit button to prevent double submit
                $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
            });

            // Print Now Button Handler - Process ALL pending labels
            $('#printNowBtn').on('click', function(e) {
                console.log('Print Now button clicked');
                
                e.preventDefault();

                const $btn = $(this);
                const queueCount = parseInt($('#queueCount').text()) || 0;

                if (queueCount === 0) {
                    alert('Tidak ada label dalam queue untuk diprint!');
                    return;
                }

                // Confirm before printing
                if (!confirm(`Print semua ${queueCount} label dalam queue sekarang?`)) {
                    return;
                }

                // Disable button and show loading
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');

                // Send AJAX request to print all pending labels
                $.ajax({
                    url: '{{ route("member.label.printNow") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(`${response.processed_count} label berhasil dikirim ke auto print queue!`);

                            // Clear the table visually — DataTables will show emptyTable message
                            table.clear().draw();


                            // Update counters
                            $('#queueCount').text('0');
                            $btn.prop('disabled', true);

                            // Reload page after 1.5 seconds to refresh data
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            alert('Error: ' + (response.message || 'Gagal memproses print queue'));
                        }
                    },
                    error: function(xhr) {
                        let message = 'Terjadi kesalahan saat memproses request';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        alert('Error: ' + message);
                    },
                    complete: function() {
                        // Re-enable button if there are still items (in case of error)
                        const currentCount = parseInt($('#queueCount').text()) || 0;
                        if (currentCount > 0) {
                            $btn.prop('disabled', false).html('<i class="fas fa-print mr-2"></i>Print Now <span class="badge badge-light ml-2" id="queueCount">' + currentCount + '</span>');
                        } else {
                            $btn.prop('disabled', true).html('<i class="fas fa-print mr-2"></i>Print Now <span class="badge badge-light ml-2" id="queueCount">0</span>');
                        }
                    }
                });
            });

            // Individual delete handler
            $(document).on('click', '.delete-label', function() {
                const id = $(this).data('id');
                const $row = $(this).closest('tr');

                if (!confirm('Hapus label ini dari queue?')) {
                    return;
                }

                $.ajax({
                    url: '{{ url("member/label") }}/' + id,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Remove row from table
                            table.row($row).remove().draw();

                            // Update counter
                            const newCount = table.rows().count();
                            $('#queueCount').text(newCount);

                            // Disable print button if empty
                            if (newCount === 0) {
                                $('#printNowBtn').prop('disabled', true);
                            }
                        } else {
                            alert('Gagal menghapus label: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat menghapus label');
                    }
                });
            });
        });
    </script>
@endsection
