<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel; // alias supaya tidak bentrok

class RequestController extends Controller
{
    public function index()
    {
        return view('users.requests.index');
    }

    public function create(Request $request)
{
    $date = Carbon::today()->format('Y-m-d'); // simpan dalam format string
    $timeNow = Carbon::now()->format('H:i:s');
    $Id_User = session('Id_Member');

    $request->validate([
        'Code_Item' => 'required',
        'Code_Rack' => 'required',
        'Sum_Request' => 'required|integer|min:1',
    ], [
        'Code_Item.required' => 'Kode item wajib diisi',
        'Code_Rack.required' => 'Kode rack wajib diisi',
        'Sum_Request.required' => 'Jumlah permintaan wajib diisi',
        'Sum_Request.integer' => 'Jumlah permintaan harus berupa angka',
        'Sum_Request.min' => 'Jumlah permintaan minimal 1',
    ]);

    $codeItem = substr($request->input('Code_Item'), 0, 12);

    $newRequest = new RequestModel();
    $newRequest->Day_Request = $date;
    $newRequest->Time_Request = $timeNow;
    $newRequest->Code_Item_Rack = $codeItem;    
    $newRequest->Code_Rack = $request->input('Code_Rack');
    $newRequest->Id_User = $Id_User;

    // Tambahkan ini:
    $newRequest->Sum_Request = $request->input('Sum_Request');

    // Jika kolom Correctness ada di DB dan ingin diisi
    if ($request->filled('Correctness')) {
        $newRequest->Correctness_Request = $request->input('Correctness');
    }

    $newRequest->save();

    return redirect()->route('submission');
}


    public function check(Request $request)
    {
        $codeRack = $request->input('Code_Rack');
        $codeItem = substr($request->input('Code_Item'), 0, 10);

        $exists = RequestModel::where('Code_Rack', $codeRack)
            ->where('Code_Item_Request', 'LIKE', '%' . $codeItem . '%')
            ->exists();

        return response()->json([
            'status' => $exists ? 'correct' : 'incorrect'
        ]);
    }
}
