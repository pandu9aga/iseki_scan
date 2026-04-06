@extends('layouts.main')

@section('style')
<style>
    .queue-card {
        transition: all 0.3s ease;
    }
    .queue-card.removing {
        opacity: 0;
        transform: translateX(30px);
    }
    .badge-pending { background-color: #f6c23e; color: #333; }
    .badge-sending { background-color: #36b9cc; color: white; }
    .badge-sent    { background-color: #1cc88a; color: white; }
    .badge-failed  { background-color: #e74a3b; color: white; }
    #statusBar {
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .msg-preview {
        font-family: monospace;
        font-size: 0.85rem;
        white-space: pre-wrap;
        background: #f8f9fc;
        border-radius: 8px;
        padding: 10px;
        border-left: 4px solid #4e73df;
    }
    .pulse {
        animation: pulse 1.5s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.5; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fab fa-whatsapp text-success mr-2"></i> WA Queue Monitor
        </h1>
        <div class="d-flex align-items-center gap-2">
            <span class="badge badge-pill badge-primary font-weight-normal mr-2" id="pendingCount">
                <i class="fas fa-clock mr-1"></i><span id="countLabel">{{ $queues->count() }}</span> Pending
            </span>
            <button id="btnAutoSend" class="btn btn-success btn-sm shadow-sm">
                <i class="fas fa-paper-plane mr-1"></i> Mulai Auto-Send
            </button>
            <button id="btnStop" class="btn btn-danger btn-sm shadow-sm d-none">
                <i class="fas fa-stop mr-1"></i> Stop
            </button>
            <button id="btnRefresh" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
        </div>
    </div>

    {{-- Status bar --}}
    <div id="statusBar" class="alert alert-info py-2 mb-3 d-none" role="alert">
        <i class="fas fa-spinner fa-spin mr-2"></i>
        <span id="statusText">Mengirim pesan...</span>
    </div>

    {{-- Stats row --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statPending">{{ $queues->count() }}</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-clock fa-2x text-warning"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Terkirim</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statSent">0</div>
                        </div>
                        <div class="col-auto"><i class="fab fa-whatsapp fa-2x text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Gagal</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statFailed">0</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-exclamation-triangle fa-2x text-danger"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Auto-Refresh</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statMode">Off</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-robot fa-2x text-info"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Queue list --}}
    <div class="card shadow mb-4">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h6 class="m-0 font-weight-bold text-primary">Antrian Pesan WA</h6>
            <small class="text-muted">Server: <code>{{ url('/') }}</code></small>
        </div>
        <div class="card-body">
            <div id="queueList">
                @forelse($queues as $q)
                <div class="card queue-card mb-3 border" id="card-{{ $q->id }}" data-id="{{ $q->id }}" data-message="{{ htmlspecialchars($q->message) }}" data-groupid="{{ $q->group_id }}">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1 mr-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-pending mr-2">PENDING</span>
                                    <small class="text-muted">#{{ $q->id }} &bull; {{ $q->created_at->format('d-M-Y H:i:s') }}</small>
                                </div>
                                <div class="msg-preview">{{ $q->message }}</div>
                            </div>
                            <div class="d-flex flex-column">
                                <button class="btn btn-sm btn-success mb-1 btn-send-one" data-id="{{ $q->id }}">
                                    <i class="fas fa-paper-plane"></i> Kirim
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-one" data-id="{{ $q->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5" id="emptyState">
                    <i class="fab fa-whatsapp fa-4x text-success mb-3"></i>
                    <h5 class="text-muted">Tidak ada antrian pesan</h5>
                    <p class="text-muted">Semua pesan sudah terkirim!</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script>
    const PROXY_URL  = 'https://wablas-proxy.isekipandu.workers.dev'; // No trailing slash
    const WA_TOKEN   = 't5wx1eefCYvMchKePC5OCj0j7UdURmj4omtoaCqfmDtCA4pWpeZycH9.w9A02qKW'; // MUST be TOKEN.SECRET_KEY
    const FETCH_URL  = '{{ route('wa.queue.fetch') }}';
    const SENT_BASE  = '{{ url('/api/wa-queue') }}';
    const SENT_URL   = id => `${SENT_BASE}/${id}/sent`;
    const FAIL_BASE  = '{{ url('/api/wa-queue') }}';
    const FAIL_URL   = id => `${FAIL_BASE}/${id}/failed`;
    const CANCEL_BASE = '{{ url('/api/wa-queue') }}';
    const CANCEL_URL  = id => `${CANCEL_BASE}/${id}`;
    const CSRF_TOKEN = '{{ csrf_token() }}';

    let isRunning = false;
    let autoInterval = null;
    let sentCount = 0;
    let failedCount = 0;

    // ── Helpers ──────────────────────────────────────────────

    function setStatus(text, visible = true) {
        const bar = document.getElementById('statusBar');
        document.getElementById('statusText').textContent = text;
        bar.classList.toggle('d-none', !visible);
    }

    function updateStats(pendingCount) {
        document.getElementById('statPending').textContent = pendingCount;
        document.getElementById('statSent').textContent    = sentCount;
        document.getElementById('statFailed').textContent  = failedCount;
        document.getElementById('countLabel').textContent  = pendingCount;
    }

    function removeCard(id) {
        const card = document.getElementById('card-' + id);
        if (card) {
            card.classList.add('removing');
            setTimeout(() => card.remove(), 350);
        }
    }

    function showEmptyIfNeeded() {
        const list = document.getElementById('queueList');
        if (list.querySelectorAll('.queue-card:not(.removing)').length === 0) {
            list.innerHTML = `
                <div class="text-center py-5" id="emptyState">
                    <i class="fab fa-whatsapp fa-4x text-success mb-3"></i>
                    <h5 class="text-muted">Tidak ada antrian pesan</h5>
                    <p class="text-muted">Semua pesan sudah terkirim!</p>
                </div>`;
        }
    }

    // ── Send a single WA message via Wablas API ───────────────

    async function sendWa(id, message, groupId) {
        const card = document.getElementById('card-' + id);
        if (card) {
            card.querySelector('.badge').textContent = 'MENGIRIM';
            card.querySelector('.badge').className = 'badge badge-sending mr-2 pulse';
        }

        try {
            const resp = await fetch(PROXY_URL + '/api/v2/send-message', {
                method: 'POST',
                headers: {
                    'Authorization': WA_TOKEN,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    data: [{
                        phone: groupId,
                        message: message,
                        isGroup: "true"
                    }]
                })
            });

            const result = await resp.json();

            if (resp.ok && (result.status === true || result.status === 'success')) {
                // Mark as sent on server
                await fetch(SENT_URL(id), {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
                });
                sentCount++;
                removeCard(id);
                return true;
            } else {
                console.error('Wablas Proxy Error:', result);
                
                // Mark as failed on server
                await fetch(FAIL_URL(id), {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
                });
                failedCount++;
                if (card) {
                    card.querySelector('.badge').textContent = 'GAGAL';
                    card.querySelector('.badge').className = 'badge badge-failed mr-2';
                }
                return false;
            }
        } catch (err) {
            console.error('Network Error:', err);
            failedCount++;
            if (card) {
                card.querySelector('.badge').textContent = 'ERR: PROXY';
                card.querySelector('.badge').className = 'badge badge-failed mr-2';
            }
            return false;
        }
    }

    // ── Fetch latest queue from server and render ─────────────

    async function refreshQueue() {
        try {
            const resp = await fetch(FETCH_URL, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            const list = document.getElementById('queueList');
            list.innerHTML = '';

            if (data.length === 0) {
                list.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fab fa-whatsapp fa-4x text-success mb-3"></i>
                        <h5 class="text-muted">Tidak ada antrian pesan</h5>
                        <p class="text-muted">Semua pesan sudah terkirim!</p>
                    </div>`;
            } else {
                data.forEach(q => {
                    const escapedMsg = q.message.replace(/"/g, '&quot;');
                    list.innerHTML += `
                        <div class="card queue-card mb-3 border" id="card-${q.id}" data-id="${q.id}" data-message="${escapedMsg}" data-groupid="${q.group_id}">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1 mr-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge badge-pending mr-2">PENDING</span>
                                            <small class="text-muted">#${q.id} &bull; ${q.created_at}</small>
                                        </div>
                                        <div class="msg-preview">${q.message.replace(/</g,'&lt;')}</div>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <button class="btn btn-sm btn-success mb-1 btn-send-one" data-id="${q.id}">
                                            <i class="fas fa-paper-plane"></i> Kirim
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-one" data-id="${q.id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });
            }
            updateStats(data.length);
        } catch (err) {
            console.error('Refresh error:', err);
        }
    }

    // ── Auto-Send Loop ────────────────────────────────────────

    async function autoSendLoop() {
        setStatus('Mengambil antrian dari server...', true);
        const resp = await fetch(FETCH_URL, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();

        updateStats(data.length);

        if (data.length === 0) {
            setStatus('✅ Semua pesan terkirim! Menunggu pesan baru...', true);
            return;
        }

        for (const q of data) {
            if (!isRunning) break;
            setStatus(`Mengirim pesan #${q.id} ke grup WhatsApp...`, true);
            await sendWa(q.id, q.message, q.group_id);
            // Small delay between sends
            await new Promise(r => setTimeout(r, 1500));
        }

        // Update pending count
        const remaining = document.querySelectorAll('.queue-card:not(.removing)').length;
        updateStats(remaining);
        showEmptyIfNeeded();
    }

    // ── Button Events ─────────────────────────────────────────

    document.getElementById('btnAutoSend').addEventListener('click', function () {
        isRunning = true;
        document.getElementById('btnAutoSend').classList.add('d-none');
        document.getElementById('btnStop').classList.remove('d-none');
        document.getElementById('statMode').textContent = 'ON';
        setStatus('Auto-send aktif...', true);

        // Run immediately then every 10 seconds
        autoSendLoop();
        autoInterval = setInterval(() => {
            if (isRunning) autoSendLoop();
        }, 10000);
    });

    document.getElementById('btnStop').addEventListener('click', function () {
        isRunning = false;
        clearInterval(autoInterval);
        document.getElementById('btnStop').classList.add('d-none');
        document.getElementById('btnAutoSend').classList.remove('d-none');
        document.getElementById('statMode').textContent = 'Off';
        setStatus('Auto-send dihentikan.', true);
        setTimeout(() => setStatus('', false), 3000);
    });

    document.getElementById('btnRefresh').addEventListener('click', refreshQueue);

    // Event delegation for individual send/delete buttons
    document.getElementById('queueList').addEventListener('click', async function (e) {
        const sendBtn = e.target.closest('.btn-send-one');
        const delBtn  = e.target.closest('.btn-delete-one');

        if (sendBtn) {
            const id   = sendBtn.dataset.id;
            const card = document.getElementById('card-' + id);
            const msg  = card.dataset.message.replace(/&quot;/g, '"');
            const gid  = card.dataset.groupid;
            sendBtn.disabled = true;
            await sendWa(id, msg, gid);
            const remaining = document.querySelectorAll('.queue-card:not(.removing)').length;
            updateStats(remaining);
            showEmptyIfNeeded();
        }

        if (delBtn) {
            if (!confirm('Hapus pesan ini dari antrian tanpa mengirim?')) return;
            const id = delBtn.dataset.id;
            await fetch(CANCEL_URL(id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
            });
            removeCard(id);
            setTimeout(() => {
                const remaining = document.querySelectorAll('.queue-card:not(.removing)').length;
                updateStats(remaining);
                showEmptyIfNeeded();
            }, 400);
        }
    });
</script>
@endsection
