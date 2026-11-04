<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Check;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserCheckController extends Controller
{
    /**
     * Show check list page with DataTable
     */
    public function index(Request $request)
    {
        // Otomatis filter "hari ini" berdasarkan tanggal input (Time_Check) jika tanggal tidak dipilih
        $date  = $request->input('date', Carbon::today()->format('Y-m-d'));
        $month = $request->input('month'); // format YYYY-MM

        // Filter by waktu input (Time_Check): bulanan jika dipilih, selain itu harian
        $timeFilter = function ($q) use ($date, $month) {
            if ($month) {
                $q->whereYear('Time_Check', substr($month, 0, 4))
                  ->whereMonth('Time_Check', (int)substr($month, 5, 2));
            } else {
                $q->whereDate('Time_Check', $date);
            }
        };

        // Tampilkan semua check: yang punya target date (Status_Check) ATAU hasil auto-scan (Auto_Check = 1)
        $query = Check::where(function ($q) {
            $q->whereNotNull('Status_Check')
              ->orWhere('Auto_Check', 1);
        });
        $timeFilter($query);

        // Filter by checker (Id_User)
        if ($checker = $request->input('checker')) {
            $query->where('Id_User', $checker);
        }

        $checks = $query->orderBy('Id_Checks', 'desc')->get();

        // Ambil ID hanya dari data yang sudah terfilter untuk map nama di tabel
        $memberIds = $checks->filter(fn($c) => !$c->Is_User)->pluck('Id_User')->filter()->unique();
        $adminIds  = $checks->filter(fn($c) => $c->Is_User)->pluck('Id_User')->filter()->unique();

        $membersMap = $memberIds->isNotEmpty()
            ? \App\Models\Member::whereIn('Id_Member', $memberIds)->pluck('Name_Member', 'Id_Member')->toArray()
            : [];

        $usersMap = $adminIds->isNotEmpty()
            ? \App\Models\User::whereIn('Id_User', $adminIds)->pluck('Username_User', 'Id_User')->toArray()
            : [];

        // checkerList untuk dropdown - hanya checker yang check pada waktu input yang dipilih (harian/bulanan)
        $allChecks = Check::select('Id_User', 'Is_User');
        $timeFilter($allChecks);
        $allChecks = $allChecks->distinct()->get();
        $allMemberIds = $allChecks->filter(fn($c) => !$c->Is_User)->pluck('Id_User')->filter()->unique();
        $allAdminIds  = $allChecks->filter(fn($c) => $c->Is_User)->pluck('Id_User')->filter()->unique();

        $checkerList = [];
        if ($allMemberIds->isNotEmpty()) {
            $members = \App\Models\Member::whereIn('Id_Member', $allMemberIds)->pluck('Name_Member', 'Id_Member');
            foreach ($members as $id => $name) {
                $checkerList[$id] = $name;
            }
        }
        if ($allAdminIds->isNotEmpty()) {
            $admins = \App\Models\User::whereIn('Id_User', $allAdminIds)->pluck('Username_User', 'Id_User');
            foreach ($admins as $id => $name) {
                $checkerList[$id] = $name . ' (Admin)';
            }
        }
        asort($checkerList);

        // Rack map hanya dari data terfilter
        $codes = $checks->pluck('Code_Rack')->filter()->unique();
        $racksMap = $codes->isNotEmpty()
            ? \App\Models\Rack::whereIn('Code_Rack', $codes)->pluck('Name_Item_Rack', 'Code_Rack')->toArray()
            : [];

        foreach ($checks as $c) {
            $c->checker_name = $c->Is_User
                ? (($usersMap[$c->Id_User] ?? '-') . ' (Admin)')
                : ($membersMap[$c->Id_User] ?? '-');
            $c->rack_name = $racksMap[$c->Code_Rack] ?? '-';
        }

        return view('users.checks.index', compact('checks', 'checkerList'));
    }

    /**
     * Store a check from the request scan page
     */
    public function store(Request $request)
    {
        $request->validate([
            'Code_Rack' => 'required|string',
            'Code_Item' => 'required|string',
            'Status_Check' => 'required|integer|min:1',
        ]);

        $now = Carbon::now();
        $statusDays = intval($request->input('Status_Check'));
        // Hitung target date menggunakan hari kerja (skip weekend & hari libur)
        $targetDate = \App\Models\SpecialDate::addWorkdays($now, $statusDays)->format('Y-m-d');

        // Jika ada check_id, tandai check lama sebagai selesai
        if ($request->has('check_id') && !empty($request->input('check_id'))) {
            $oldCheck = Check::find($request->input('check_id'));
            if ($oldCheck) {
                $oldCheck->Status_Check = null;
                $oldCheck->save();
            }
        }

        Check::create([
            'Time_Check' => $now,
            'Code_Rack' => $request->input('Code_Rack'),
            'Code_Item_Rack' => substr($request->input('Code_Item'), 0, 12),
            'Id_User' => session('Id_Member'),
            'Status_Check' => $targetDate,
            'Is_User' => 0,
            'Area_Check' => $request->input('Area_Request'),
        ]);

        $label = $request->input('Status_Check') . ' Hari Ke Depan';
        $codeRack = $request->input('Code_Rack');

        return redirect()->route('request', [
            'area' => $request->input('Area_Request'),
            'code_rack' => $codeRack,
            'code_item' => $request->input('Code_Item'),
        ])->with('success', $codeRack.' '.$label.' berhasil disimpan.');
    }

    /**
     * Mark a check as done (sets Status_Check to NULL)
     */
    public function markAsDone(Request $request, $id)
    {
        $check = Check::findOrFail($id);
        $check->Status_Check = null;
        $check->save();

        $filterParams = array_filter($request->only(['date', 'month', 'checker']));
        return redirect()->route('user.check', $filterParams)->with('success', 'Check berhasil ditandai sebagai Selesai.');
    }

    /**
     * Auto-record check from barcode scan (AJAX endpoint for user request page).
     * Saves Code_Rack to checks with Status_Check = NULL.
     * Prevents double-record: 1 rack = 1 record per user per day.
     */
    public function autoStore(Request $request)
    {
        $request->validate([
            'code_rack'  => 'required|string',
            'code_item'  => 'nullable|string',
            'area'       => 'nullable|string',
        ]);

        $idMember  = session('Id_Member');
        $today     = Carbon::today();
        $codeRack  = strtoupper($request->input('code_rack'));
        $codeItem  = substr(($request->input('code_item') ?? ''), 0, 12);
        $area      = $request->input('area');

        // Cek: apakah user ini sudah scan rak yang sama hari ini (khusus record auto-scan)?
        $alreadyExists = Check::where('Code_Rack', $codeRack)
            ->where('Id_User', $idMember)
            ->where('Is_User', 0)
            ->where('Auto_Check', 1)
            ->whereDate('Time_Check', $today)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'saved'   => false,
                'message' => 'Check untuk rak ini sudah tercatat hari ini.',
            ]);
        }

        Check::create([
            'Time_Check'    => Carbon::now(),
            'Code_Rack'     => $codeRack,
            'Code_Item_Rack' => $codeItem ?: '',
            'Id_User'       => $idMember,
            'Status_Check'  => null,   // Target date null — tidak perlu pilih hari
            'Is_User'       => 0,
            'Auto_Check'    => 1,
            'Area_Check'    => $area,
        ]);

        return response()->json([
            'saved'   => true,
            'message' => $codeRack . ' berhasil dicatat ke Check.',
        ]);
    }
}
