<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use App\Models\WaQueue;

class WaQueueController extends Controller
{
    const WA_GROUP_ID = '120363045467407165@g.us';

    const WA_TOKEN = 'NOFl7qr6DjYqG4jiy3MOmecZrzPfqkCeLQh76lpawgIRAi6ZSKfPXOB';

    const WA_HOST = 'https://solo.wablas.com/';

    /**
     * Halaman monitoring antrian WA
     */
    public function index()
    {
        $queues = WaQueue::where('status', 'pending')->orderBy('created_at', 'asc')->get();

        return view('admins.wa_queue', compact('queues'));
    }

    /**
     * API: ambil semua pending messages dalam format JSON
     */
    public function fetch()
    {
        $queues = WaQueue::where('status', 'pending')->orderBy('created_at', 'asc')->get();

        return response()->json($queues);
    }

    /**
     * API: tandai pesan sebagai terkirim
     */
    public function markSent($id)
    {
        $queue = WaQueue::findOrFail($id);
        $queue->update(['status' => 'sent']);

        return response()->json(['success' => true, 'message' => 'Pesan ditandai terkirim.']);
    }

    /**
     * API: hapus pesan (opsional, tapi user minta tidak hapus, jadi kita bisa simpan destroy tapi mungkin ubah status ke cancelled)
     */
    public function destroy($id)
    {
        $queue = WaQueue::findOrFail($id);
        $queue->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Pesan dibatalkan.']);
    }

    /**
     * API: tandai pesan sebagai gagal
     */
    public function markFailed($id)
    {
        $queue = WaQueue::findOrFail($id);
        $queue->update(['status' => 'failed']);

        return response()->json(['success' => true, 'message' => 'Pesan ditandai gagal.']);
    }
}
