<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Check;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdminCheckController extends Controller
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

        // === Ringkasan checker hari ini (khusus halaman admin) ===
        $todayChecks = Check::whereDate('Time_Check', Carbon::today())->get();
        $todayMemberIds = $todayChecks->filter(fn($c) => !$c->Is_User)->pluck('Id_User')->filter()->unique();
        $todayAdminIds  = $todayChecks->filter(fn($c) => $c->Is_User)->pluck('Id_User')->filter()->unique();

        $todayMembersMap = $todayMemberIds->isNotEmpty()
            ? \App\Models\Member::whereIn('Id_Member', $todayMemberIds)->pluck('Name_Member', 'Id_Member')->toArray()
            : [];
        $todayUsersMap = $todayAdminIds->isNotEmpty()
            ? \App\Models\User::whereIn('Id_User', $todayAdminIds)->pluck('Username_User', 'Id_User')->toArray()
            : [];

        $checkerSummary = [];
        foreach ($todayChecks as $c) {
            $name = $c->Is_User
                ? (($todayUsersMap[$c->Id_User] ?? '-') . ' (Admin)')
                : ($todayMembersMap[$c->Id_User] ?? '-');
            $checkerSummary[$name] = ($checkerSummary[$name] ?? 0) + 1;
        }
        arsort($checkerSummary);
        $todayTotal = $todayChecks->count();

        return view('admins.checks.index', compact('checks', 'checkerList', 'checkerSummary', 'todayTotal'));
    }

    /**
     * Export filtered checks to Excel (newest data on top, oldest at the bottom)
     */
    public function export(Request $request)
    {
        $date  = $request->input('date', Carbon::today()->format('Y-m-d'));
        $month = $request->input('month'); // format YYYY-MM

        $timeFilter = function ($q) use ($date, $month) {
            if ($month) {
                $q->whereYear('Time_Check', substr($month, 0, 4))
                  ->whereMonth('Time_Check', (int)substr($month, 5, 2));
            } else {
                $q->whereDate('Time_Check', $date);
            }
        };

        $query = Check::where(function ($q) {
            $q->whereNotNull('Status_Check')
              ->orWhere('Auto_Check', 1);
        });
        $timeFilter($query);

        if ($checker = $request->input('checker')) {
            $query->where('Id_User', $checker);
        }

        // Terbaru di atas, terlama di bawah
        $checks = $query->orderBy('Time_Check', 'desc')
            ->orderBy('Id_Checks', 'desc')
            ->get();

        $memberIds = $checks->filter(fn($c) => !$c->Is_User)->pluck('Id_User')->filter()->unique();
        $adminIds  = $checks->filter(fn($c) => $c->Is_User)->pluck('Id_User')->filter()->unique();

        $membersMap = $memberIds->isNotEmpty()
            ? \App\Models\Member::whereIn('Id_Member', $memberIds)->pluck('Name_Member', 'Id_Member')->toArray()
            : [];

        $usersMap = $adminIds->isNotEmpty()
            ? \App\Models\User::whereIn('Id_User', $adminIds)->pluck('Username_User', 'Id_User')->toArray()
            : [];

        $codes = $checks->pluck('Code_Rack')->filter()->unique();
        $racksMap = $codes->isNotEmpty()
            ? \App\Models\Rack::whereIn('Code_Rack', $codes)->pluck('Name_Item_Rack', 'Code_Rack')->toArray()
            : [];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['No', 'Time Check', 'Area Check', 'Rack', 'Item', 'Name', 'Checker'];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter('A1:G1');

        $row = 2;
        $no = 1;

        foreach ($checks as $c) {
            $checkerName = $c->Is_User
                ? (($usersMap[$c->Id_User] ?? '-') . ' (Admin)')
                : ($membersMap[$c->Id_User] ?? '-');

            $sheet->fromArray([
                $no,
                $c->Time_Check ? Carbon::parse($c->Time_Check)->format('d/m/y H:i') : '-',
                $c->Area_Check ?? '-',
                $c->Code_Rack,
                $c->Code_Item_Rack,
                $racksMap[$c->Code_Rack] ?? '-',
                $checkerName,
            ], null, 'A' . $row);

            $no++;
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Check_' . ($month ?: $date) . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Store a check from the admin requesting page
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
        $targetDate = $now->copy()->addDays($statusDays)->format('Y-m-d');

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
            'Id_User' => session('Id_User'),
            'Status_Check' => $targetDate,
            'Is_User' => 1,
            'Area_Check' => $request->input('Area_Request'),
        ]);

        $label = $request->input('Status_Check') . ' Hari Ke Depan';

        return redirect()->route('admin.requesting', [
            'area' => $request->input('Area_Request'),
            'code_rack' => $request->input('Code_Rack'),
            'code_item' => $request->input('Code_Item'),
        ])->with('success', $request->input('Code_Rack').' '.$label.' berhasil disimpan.');
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
        return redirect()->route('admin.check', $filterParams)->with('success', 'Check berhasil ditandai sebagai Selesai.');
    }
}
