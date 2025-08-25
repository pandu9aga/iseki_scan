<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Record;
use App\Models\Request as RequestModel; // alias supaya tidak bentrok

class RecordController extends Controller
{
    public function index(){
        return view('users.records.index');
    }

    public function create(Request $request)
    {
        $date = Carbon::today();
        $timeNow = Carbon::now()->format('H:i:s');
        $Id_User = session('Id_Member');

        // validasi
        $request->validate([
            'Code_Item' => 'required',
            'Code_Rack' => 'required',
            'Sum_Record' => 'required|integer|min:1',
        ],
        [
            'Code_Item.required' => 'Kode item wajib diisi',
            'Code_Rack.required' => 'Kode rack wajib diisi',
            'Sum_Record.required' => 'Jumlah permintaan wajib diisi',
            'Sum_Record.integer' => 'Jumlah permintaan harus berupa angka',
            'Sum_Record.min' => 'Jumlah permintaan minimal 1',
        ]);

        $rawItem = $request->input('Code_Item');
        // hapus semua spasi & tanda baca, hanya sisakan huruf/angka
        $cleanItem = preg_replace('/[^\p{L}\p{N}]/u', '', $rawItem);
        // ambil 12 karakter pertama
        $codeItem = substr($cleanItem, 0, 12);

        DB::beginTransaction();
        try {
            // cari request yang matching
            $matchingRequest = RequestModel::where('Code_Item_Rack', $codeItem)
                // ->where('Code_Rack', $request->input('Code_Rack'))
                ->where('Id_User', $Id_User)
                ->where('Status_Request', 'Waiting')
                ->first();

            $Id_Request = null;

            if ($matchingRequest) {
                // update status jadi Done
                $matchingRequest->Status_Request = 'Done';
                $matchingRequest->save();

                $Id_Request = $matchingRequest->Id_Request; // ambil id
            }

            // insert record
            Record::create([
                'Day_Record' => $date,
                'Time_Record' => $timeNow,
                'Code_Item_Rack' => $codeItem,
                'Code_Rack' => $request->input('Code_Rack'),
                'Correctness_Record' => $request->input('Correctness'),
                'Sum_Record' => $request->input('Sum_Record'),
                'Id_User' => $Id_User,
                'Id_Request' => $Id_Request, // bisa null kalau tidak ada
            ]);

            DB::commit();
            return redirect()->route('user_report')->with('success', 'Record berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('user_report')->with('error', 'Gagal membuat record: '.$e->getMessage());
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

        return response()->json([
            'status' => $exists ? 'correct' : 'incorrect'
        ]);
    }

}
