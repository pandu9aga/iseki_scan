<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Request as RequestModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PredictionController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        // Ambil semua request untuk tanggal tersebut
        $requests = RequestModel::with('member', 'rack')
            ->where('Day_Request', $date)
            ->get();

        // Ambil semua kategori Area untuk One-Hot Encoding (OHE)
        $areas = RequestModel::whereNotNull('Area_Request')
            ->distinct()
            ->pluck('Area_Request')
            ->toArray();
        if (!in_array('Unknown', $areas)) {
            $areas[] = 'Unknown';
        }
        sort($areas); // Penting: urutan harus konsisten dengan saat training

        // Siapkan data dataset untuk view
        $dataset = $requests->map(function ($req) {
            $time = Carbon::parse($req->Time_Request);
            $dayOfWeek = Carbon::parse($req->Day_Request)->dayOfWeek + 1;
            
            return [
                'Id_Request' => $req->Id_Request,
                'Day_Of_Week' => (float)$dayOfWeek,
                'Hour_Of_Day' => (float)$time->hour,
                'Member_ID' => (float)$req->Id_User,
                'Part_Code_Raw' => $req->Code_Item_Rack,
                'Part_Code_Num' => (float)filter_var($req->Code_Item_Rack, FILTER_SANITIZE_NUMBER_INT),
                'Requested_Quantity' => (float)$req->Sum_Request,
                'Is_Urgent' => (float)($req->Urgent_Request ?? 0),
                'Area' => $req->Area_Request ?? 'Unknown',
                'Was_Delayed_DST' => 0.0, 
                'Member_Name' => $req->member->Name_Member ?? 'Unknown',
                'Part_Name' => $req->rack->Name_Item_Rack ?? 'N/A',
            ];
        });

        return view('admins.predictions.error', compact('date', 'requests', 'dataset', 'areas'));
    }
}
