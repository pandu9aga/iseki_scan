<?php

namespace App\Http\Controllers\Transit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;

class TransitScanController extends Controller
{
    public function index()
    {
        return view('transits.scan');
    }

    public function process(Request $request)
    {
        $request->validate([
            'Code_Item' => 'required',
        ], [
            'Code_Item.required' => 'Kode item wajib diisi',
        ]);

        // Bersihkan input Code_Item seperti di RecordController
        $rawItem   = $request->input('Code_Item');
        $cleanItem = preg_replace('/[^\p{L}\p{N}]/u', '', $rawItem);
        $codeItem  = substr($cleanItem, 0, 12);

        // Cari request dengan Code_Item_Rack (like) dimana Status_Request bukan 'Done' dan Ready_Request null
        $requestModel = RequestModel::with(['member', 'rack'])
            ->where('Code_Item_Rack', 'LIKE', '%' . $codeItem . '%')
            ->where('Status_Request', '!=', 'Done')
            ->whereNull('Ready_Request')
            ->orderBy('Time_Request', 'asc') // prioritizing older requests
            ->first();

        if ($requestModel) {
            $requestModel->Ready_Request = Carbon::now();
            $requestModel->save();

            $details = "
                <hr>
                <div class='text-left'>
                    <strong>Rack Code:</strong> {$requestModel->Code_Rack}<br>
                    <strong>Item Code:</strong> {$requestModel->Code_Item_Rack}<br>
                    <strong>Item Name:</strong> " . ($requestModel->rack->Name_Item_Rack ?? '-') . "<br>
                    <strong>Time Request:</strong> {$requestModel->Day_Request} {$requestModel->Time_Request}<br>
                    <strong>Member Name:</strong> " . ($requestModel->member->Name_Member ?? '-') . "
                </div>
            ";

            return redirect()->back()->with('success', 'Ready Request berhasil diupdate untuk ' . $requestModel->Code_Item_Rack . $details);
        } else {
            return redirect()->back()->with('error', 'Tidak ditemukan Request yang sesuai atau Ready Request sudah terisi.');
        }
    }

    public function check(Request $request)
    {
        $codeItem = substr($request->input('Code_Item'), 0, 10); // Ambil 10 karakter pertama saja

        // Cari request dengan Code_Item_Rack (like) dimana Status_Request bukan 'Done' dan Ready_Request null
        $requestModel = RequestModel::with(['member', 'rack'])
            ->where('Code_Item_Rack', 'LIKE', '%' . $codeItem . '%')
            ->where('Status_Request', '!=', 'Done')
            ->whereNull('Ready_Request')
            ->orderBy('Time_Request', 'asc')
            ->first();

        if ($requestModel) {
            return response()->json([
                'status' => 'correct',
                'details' => [
                    'rack_code' => $requestModel->Code_Rack,
                    'item_code' => $requestModel->Code_Item_Rack,
                    'item_name' => $requestModel->rack->Name_Item_Rack ?? '-',
                    'time_request' => $requestModel->Day_Request . ' ' . $requestModel->Time_Request,
                    'member_name' => $requestModel->member->Name_Member ?? '-'
                ]
            ]);
        }

        return response()->json([
            'status' => 'incorrect'
        ]);
    }
}
