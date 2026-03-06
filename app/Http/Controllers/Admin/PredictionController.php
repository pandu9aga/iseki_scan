<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Request as RequestModel;
use Carbon\Carbon;

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
    public function emptiness()
    {
        // 1. Get recent fill stats (records)
        $fillStats = DB::table('records')
            ->select('Code_Rack', DB::raw('COUNT(*) as fill_count_7d'))
            ->where('Day_Record', '>=', now()->subDays(7))
            ->groupBy('Code_Rack');

        // 2. Get recent request stats (requests by rack)
        $reqRackStats = DB::table('requests')
            ->select('Code_Rack', 
                DB::raw('COUNT(*) as request_count_7d'),
                DB::raw('MAX(STR_TO_DATE(CONCAT(Day_Request, " ", Time_Request), "%Y-%m-%d %H:%i:%s")) as last_req_time')
            )
            ->where('Day_Request', '>=', now()->subDays(7))
            ->groupBy('Code_Rack');

        // 3. Get habit stats (requests by item)
        $habitStats = DB::table('requests')
            ->select('Code_Item_Rack',
                DB::raw('COUNT(*) as total_req_lifetime'),
                DB::raw('MIN(STR_TO_DATE(CONCAT(Day_Request, " ", Time_Request), "%Y-%m-%d %H:%i:%s")) as first_req_time'),
                DB::raw('MAX(STR_TO_DATE(CONCAT(Day_Request, " ", Time_Request), "%Y-%m-%d %H:%i:%s")) as max_req_time')
            )
            ->groupBy('Code_Item_Rack');

        // 4. Combine everything
        $racks = DB::table('racks as r')
            ->leftJoinSub($fillStats, 'f', 'r.Code_Rack', '=', 'f.Code_Rack')
            ->leftJoinSub($reqRackStats, 'rr', 'r.Code_Rack', '=', 'rr.Code_Rack')
            ->leftJoinSub($habitStats, 'h', 'r.Code_Item_Rack', '=', 'h.Code_Item_Rack')
            ->select([
                'r.Code_Rack',
                'r.Code_Item_Rack',
                DB::raw('COALESCE(f.fill_count_7d, 0) as fill_count_7d'),
                DB::raw('COALESCE(rr.request_count_7d, 0) as request_count_7d'),
                DB::raw('COALESCE(h.total_req_lifetime, 0) as total_req_lifetime'),
                DB::raw('TIMESTAMPDIFF(HOUR, rr.last_req_time, NOW()) as hours_since_last_req'),
                DB::raw('TIMESTAMPDIFF(HOUR, h.first_req_time, h.max_req_time) / NULLIF(h.total_req_lifetime - 1, 0) as avg_request_interval_h')
            ])
            ->get();

        return view('admins.predictions.emptiness', compact('racks'));
    }
}
