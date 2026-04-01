<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use App\Models\WaQueue;
use Illuminate\Http\Request;

class WaQueueController extends Controller
{
    const WA_GROUP_ID = 'true_120363417614072057@g.us_3EB060ECE12DE31EBADF26_187381403668615@lid';
    const WA_TOKEN = 'uTpuO0BweAI485fbGD2e3ERQLiMSlMss98iqfWDefGLkJl36H46zN9v';
    const WA_HOST = 'https://kudus.wablas.com/';

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
     * API: tandai pesan sebagai terkirim dan hapus dari antrian
     */
    public function destroy($id)
    {
        $queue = WaQueue::findOrFail($id);
        $queue->delete();
        return response()->json(['success' => true, 'message' => 'Pesan berhasil dihapus dari antrian.']);
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
