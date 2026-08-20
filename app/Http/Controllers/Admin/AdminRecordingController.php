<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Record;
use App\Models\Request as RequestModel;
use App\Models\SumMismatch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRecordingController extends Controller
{
    public function index()
    {
        return view('admins.recording.index');
    }

    public function create(Request $request)
    {
        $date = Carbon::today();
        $timeNow = Carbon::now()->format('H:i:s');

        // Ambil ID admin dari session (dari tabel users)
        $adminId = session('Id_User');

        // Validasi: ID admin harus ada, tidak boleh null
        if (!$adminId) {
            return redirect()->back()->with('error', 'Session admin tidak ditemukan. Silakan login ulang.');
        }

        // Validasi input
        $validated = $request->validate([
            'Code_Item' => 'required',
            'Code_Rack' => 'required',
            'Sum_Record' => 'required|integer|min:1',
        ], [
            'Code_Item.required' => 'Kode item wajib diisi',
            'Code_Rack.required' => 'Kode rack wajib diisi',
            'Sum_Record.required' => 'Jumlah permintaan wajib diisi',
            'Sum_Record.integer' => 'Jumlah permintaan harus berupa angka',
            'Sum_Record.min' => 'Jumlah permintaan minimal 1',
        ]);

        // Bersihkan input Code_Item
        $rawItem = $validated['Code_Item'];
        $cleanItem = preg_replace('/[^\p{L}\p{N}]/u', '', $rawItem);
        $codeItem = substr($cleanItem, 0, 12);

        DB::beginTransaction();
        try {
            $Id_Request = null;
            $matchingRequest = null;

            // Jika form mengirim Id_Request → cek request terkait
            if ($request->filled('Id_Request')) {
                $matchingRequest = RequestModel::find($request->input('Id_Request'));

                if ($matchingRequest && $matchingRequest->Status_Request === 'Waiting') {
                    $matchingRequest->update(['Status_Request' => 'Done']);
                    $Id_Request = $matchingRequest->Id_Request;
                }
            }

            // Insert record baru (record selalu masuk tabel records + Id_Request)
            $record = Record::create([
                'Day_Record' => $date,
                'Time_Record' => $timeNow,
                'Code_Item_Rack' => $codeItem,
                'Code_Rack' => $validated['Code_Rack'],
                'Correctness_Record' => $request->input('Correctness'),
                'Sum_Record' => $validated['Sum_Record'],
                'Id_User' => $adminId,   // ID admin dari tabel users
                'Id_Request' => $Id_Request,
                'Is_User' => 1,          // Flag: ini dari admin
            ]);

            // Auto-deteksi Part Sum Not Match: Sum_Request - Sum_Record >= 5
            $mismatchCreated = false;
            if ($Id_Request && $matchingRequest
                && ($matchingRequest->Sum_Request - $validated['Sum_Record']) >= 5) {
                SumMismatch::create([
                    'Id_Request' => $Id_Request,
                    'Id_Record' => $record->Id_Record,
                    'Code_Item_Rack' => $codeItem,
                    'Code_Rack' => $validated['Code_Rack'],
                    'Sum_Request' => $matchingRequest->Sum_Request,
                    'Received_Qty' => $validated['Sum_Record'],
                    'Outstanding_Qty' => $matchingRequest->Sum_Request - $validated['Sum_Record'],
                    'Status' => 'open',
                    'Time_Mismatch' => Carbon::now()->format('Y-m-d H:i:s'),
                    'Reported_By' => $adminId,
                ]);
                $mismatchCreated = true;
            }

            DB::commit();

            if ($mismatchCreated) {
                return redirect()->back()->with('success', 'Record berhasil dibuat. Selisih barang tercatat sebagai "Part Sum Not Match", menunggu MC melengkapi sisa barang.');
            }

            return redirect()->back()->with('success', 'Record berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal membuat record: '.$e->getMessage());
        }
    }

    public function check(Request $request)
    {
        $codeRack = $request->input('Code_Rack');
        $codeItem = substr($request->input('Code_Item'), 0, 10);

        $exists = DB::table('racks')
            ->where('Code_Rack', $codeRack)
            ->where('Code_Item_Rack', 'LIKE', '%'.$codeItem.'%')
            ->exists();

        if (! $exists) {
            $exists = DB::connection('label')->table('rack_part_lists')
                ->where('rack_no', $codeRack)
                ->where('item_code', 'LIKE', '%'.$codeItem.'%')
                ->exists();
        }

        if (! $exists) {
            $exists = RequestModel::where('Code_Rack', $codeRack)
                ->where('Code_Item_Rack', 'LIKE', '%'.$codeItem.'%')
                ->where('Status_Request', 'Waiting')
                ->exists();
        }

        if (! $exists) {
            $exists = RequestModel::where('Code_Rack', $codeRack)
                ->where('Status_Request', 'Waiting')
                ->whereNotNull('Design_Changes_Request')
                ->where('Design_Changes_Request', '!=', '')
                ->exists();
        }

        return response()->json([
            'status' => $exists ? 'correct' : 'incorrect',
        ]);
    }

    public function getData(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        $records = Record::whereDate('Day_Record', $date)
            ->orderBy('Time_Record', 'desc')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->Id_Record,
                    'code_item' => $r->Code_Item_Rack,
                    'code_rack' => $r->Code_Rack,
                    'sum_record' => $r->Sum_Record,
                    'time' => $r->Time_Record,
                    'user' => $r->display_name,
                    'correctness' => $r->Correctness_Record,
                ];
            });

        return response()->json([
            'date' => $date,
            'records' => $records,
            'count' => $records->count(),
        ]);
    }

    public function checkMultiple(Request $request)
    {
        $codeRack = $request->input('Code_Rack');
        $rawItem = $request->input('Code_Item');

        $cleanItem = preg_replace('/[^\p{L}\p{N}]/u', '', $rawItem);
        $codeItem = substr($cleanItem, 0, 10);

        // 1. Pencocokan berdasarkan code rack dan code item dengan status request waiting
        $requests = RequestModel::where('Code_Rack', $codeRack)
            ->where('Code_Item_Rack', 'LIKE', '%'.$codeItem.'%')
            ->where('Status_Request', 'Waiting')
            ->get(['Id_Request', 'Area_Request', 'Sum_Request']);

        // 2. Jika tidak ada yang cocok, cek Design_Changes_Request
        if ($requests->isEmpty()) {
            $requests = RequestModel::where('Code_Rack', $codeRack)
                ->where('Status_Request', 'Waiting')
                ->whereNotNull('Design_Changes_Request')
                ->where('Design_Changes_Request', '!=', '')
                ->get(['Id_Request', 'Area_Request', 'Sum_Request']);
        }

        return response()->json([
            'count' => $requests->count(),
            'requests' => $requests->map(function ($r) {
                return [
                    'id' => $r->Id_Request,
                    'area' => $r->Area_Request ?: '',
                    'sum_request' => $r->Sum_Request,
                ];
            }),
        ]);
    }
}
