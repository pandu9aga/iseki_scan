<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Record;

class RecordController extends Controller
{
    public function index(){
        return view('users.records.index');
    }

    public function create(Request $request)
    {
        $date = Carbon::today();
        $timeNow = Carbon::now()->format('H:i:s');
        $Id_User = session('Id_User');

        // melakukan validasi data
        $request->validate([
            'Code_Item' => 'required',
            'Code_Rack' => 'required'
        ],
        [
            'Code_Item.required' => 'Kode item wajib diisi',
            'Code_Rack.required' => 'Kode rack wajib diisi'
        ]);

        $codeItem = substr($request->input('Code_Item'), 0, 12);
        
        //tambah data item
        DB::table('records')->insert([
            'Day_Record' => $date,
            'Time_Record' => $timeNow,
            'Code_Item_Rack' => $codeItem,
            'Code_Rack' => $request->input('Code_Rack'),
            'Correctness_Record' => $request->input('Correctness'),
            'Id_User' => $Id_User
        ]);
        
        return redirect()->route('home');
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
