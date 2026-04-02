<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Record;
use App\Models\Request as RequestModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // alias supaya tidak bentrok

class RecordController extends Controller
{
    public function index()
    {
        return view('users.records.index');
    }

    public function create(Request $request)
    {
        $date = Carbon::today();
        $timeNow = Carbon::now()->format('H:i:s');
        $Id_User = session('Id_Member');

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

            // Jika form mengirim Id_Request → cek request terkait
            if ($request->filled('Id_Request')) {
                $matchingRequest = RequestModel::find($request->input('Id_Request'));

                if ($matchingRequest && $matchingRequest->Status_Request === 'Waiting') {
                    $matchingRequest->update(['Status_Request' => 'Done']);
                    $Id_Request = $matchingRequest->Id_Request;
                }
            }

            // Insert record baru
            Record::create([
                'Day_Record' => $date,
                'Time_Record' => $timeNow,
                'Code_Item_Rack' => $codeItem,
                'Code_Rack' => $validated['Code_Rack'],
                'Correctness_Record' => $request->input('Correctness'),
                'Sum_Record' => $validated['Sum_Record'],
                'Id_User' => $Id_User,
                'Id_Request' => $Id_Request, // bisa null kalau tidak ada
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Record berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal membuat record: '.$e->getMessage());
        }
    }

    public function check(Request $request)
    {
        $codeRack = $request->input('Code_Rack');
        $codeItem = substr($request->input('Code_Item'), 0, 10); // Ambil 10 karakter pertama saja

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

        return response()->json([
            'status' => $exists ? 'correct' : 'incorrect',
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
            ->get(['Id_Request', 'Area_Request']);

        // 2. Jika tidak ada yang cocok, lakukan pengecekan terhadap request dengan code rack yang sama dengan status request waiting
        // jika ada cek apakah Design_Changes_Request nya terisi atau tidak, jika terisi maka gunakan Id_Request itu
        if ($requests->isEmpty()) {
            $requests = RequestModel::where('Code_Rack', $codeRack)
                ->where('Status_Request', 'Waiting')
                ->whereNotNull('Design_Changes_Request')
                ->where('Design_Changes_Request', '!=', '')
                ->get(['Id_Request', 'Area_Request']);
        }

        return response()->json([
            'count' => $requests->count(),
            'requests' => $requests->map(function ($r) {
                return [
                    'id' => $r->Id_Request,
                    'area' => $r->Area_Request ?: '',
                ];
            }),
        ]);
    }
}
