<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Record;
use App\Models\Request as RequestModel;
use App\Models\SumMismatch;
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
                'Id_User' => $Id_User,
                'Id_Request' => $Id_Request, // bisa null kalau tidak ada
            ]);

            // Auto-deteksi Part Sum Not Match: Sum_Request - Sum_Record >= 5
            $mismatchCreated = false;
            if (
                $Id_Request && $matchingRequest
                && ($matchingRequest->Sum_Request - $validated['Sum_Record']) >= 5
            ) {
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
                    'Reported_By' => $Id_User,
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

            return redirect()->back()->with('error', 'Gagal membuat record: ' . $e->getMessage());
        }
    }

    public function check(Request $request)
    {
        $codeRack = $request->input('Code_Rack');
        $codeItem = substr($request->input('Code_Item'), 0, 10); // Ambil 10 karakter pertama saja

        $exists = DB::table('racks')
            ->where('Code_Rack', $codeRack)
            ->where('Code_Item_Rack', 'LIKE', '%' . $codeItem . '%')
            ->exists();

        if (! $exists) {
            $exists = DB::connection('label')->table('rack_part_lists')
                ->where('rack_no', $codeRack)
                ->where('item_code', 'LIKE', '%' . $codeItem . '%')
                ->exists();
        }

        if (! $exists) {
            $exists = RequestModel::where('Code_Rack', $codeRack)
                ->where('Code_Item_Rack', 'LIKE', '%' . $codeItem . '%')
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
        $myId = session('Id_Member');

        $records = Record::whereDate('Day_Record', $date)
            ->orderBy('Time_Record', 'desc')
            ->get()
            ->map(function ($r) use ($myId) {
                return [
                    'id'         => $r->Id_Record,
                    'code_item'  => $r->Code_Item_Rack,
                    'code_rack'  => $r->Code_Rack,
                    'sum_record' => $r->Sum_Record,
                    'time'       => $r->Time_Record,
                    'user'       => $r->display_name,
                    'correctness'=> $r->Correctness_Record,
                    'id_request' => $r->Id_Request,
                    'is_mine'    => ($r->Id_User == $myId && !$r->Is_User),
                ];
            });

        return response()->json([
            'date'    => $date,
            'records' => $records,
            'count'   => $records->count(),
        ]);
    }

    /**
     * Edit Sum_Record dan otomatis recalculate Part Sum Not Match.
     */
    public function updateSum(Request $request, $id)
    {
        $request->validate([
            'Sum_Record' => 'required|integer|min:1',
        ]);

        $myId = session('Id_Member');
        $record = Record::find($id);

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Record tidak ditemukan.'], 404);
        }

        // Pastikan record ini milik member yang sedang login
        if ($record->Id_User != $myId || $record->Is_User) {
            return response()->json(['success' => false, 'message' => 'Anda tidak berhak mengedit record ini.'], 403);
        }

        $newSum = (int) $request->input('Sum_Record');

        DB::beginTransaction();
        try {
            // Update Sum_Record
            $record->update([
                'Sum_Record'        => $newSum,
                'Updated_At_Record' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            // Jika record ini terkait dengan sebuah request, recalculate mismatch
            if ($record->Id_Request) {
                $matchingRequest = RequestModel::find($record->Id_Request);

                if ($matchingRequest) {
                    // Hitung total semua sum_record dari semua record yang punya Id_Request sama
                    $totalSupplied = Record::where('Id_Request', $record->Id_Request)
                        ->sum('Sum_Record');

                    $selisih = $matchingRequest->Sum_Request - $totalSupplied;

                    // Cari SumMismatch yang masih open untuk request ini
                    $existingMismatch = SumMismatch::where('Id_Request', $record->Id_Request)
                        ->where('Status', 'open')
                        ->first();

                    if ($selisih >= 5) {
                        // Masih mismatch
                        if ($existingMismatch) {
                            // Update angka di mismatch yang sudah ada
                            $existingMismatch->update([
                                'Received_Qty'   => $totalSupplied,
                                'Outstanding_Qty' => $selisih,
                                'Updated_At_Sum'  => Carbon::now()->format('Y-m-d H:i:s'),
                                'Updated_By'      => $myId,
                            ]);
                        } else {
                            // Buat SumMismatch baru (sebelumnya tidak ada karena sum dulu cukup)
                            SumMismatch::create([
                                'Id_Request'     => $record->Id_Request,
                                'Id_Record'      => $record->Id_Record,
                                'Code_Item_Rack' => $record->Code_Item_Rack,
                                'Code_Rack'      => $record->Code_Rack,
                                'Sum_Request'    => $matchingRequest->Sum_Request,
                                'Received_Qty'   => $totalSupplied,
                                'Outstanding_Qty' => $selisih,
                                'Status'         => 'open',
                                'Time_Mismatch'  => Carbon::now()->format('Y-m-d H:i:s'),
                                'Reported_By'    => $myId,
                            ]);
                        }
                    } else {
                        // Selisih < 5: hapus permanen dari tabel sum_mismatches
                        if ($existingMismatch) {
                            $existingMismatch->delete();
                        }
                    }
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Sum Record berhasil diperbarui.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function checkMultiple(Request $request)
    {
        $codeRack = $request->input('Code_Rack');
        $rawItem = $request->input('Code_Item');

        $cleanItem = preg_replace('/[^\p{L}\p{N}]/u', '', $rawItem);
        $codeItem = substr($cleanItem, 0, 10);

        // 1. Pencocokan berdasarkan code rack dan code item dengan status request waiting
        $requests = RequestModel::where('Code_Rack', $codeRack)
            ->where('Code_Item_Rack', 'LIKE', '%' . $codeItem . '%')
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
