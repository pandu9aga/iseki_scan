<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Check;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

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

        // === Ringkasan checker sesuai filter (harian/bulanan) — khusus halaman admin ===
        $summaryQuery = Check::select('Id_User', 'Is_User');
        $timeFilter($summaryQuery);
        $periodChecks = $summaryQuery->get();
        $periodMemberIds = $periodChecks->filter(fn($c) => !$c->Is_User)->pluck('Id_User')->filter()->unique();
        $periodAdminIds  = $periodChecks->filter(fn($c) => $c->Is_User)->pluck('Id_User')->filter()->unique();

        $periodMembersMap = $periodMemberIds->isNotEmpty()
            ? \App\Models\Member::whereIn('Id_Member', $periodMemberIds)->pluck('Name_Member', 'Id_Member')->toArray()
            : [];
        $periodUsersMap = $periodAdminIds->isNotEmpty()
            ? \App\Models\User::whereIn('Id_User', $periodAdminIds)->pluck('Username_User', 'Id_User')->toArray()
            : [];

        $checkerSummary = [];
        foreach ($periodChecks as $c) {
            $name = $c->Is_User
                ? (($periodUsersMap[$c->Id_User] ?? '-') . ' (Admin)')
                : ($periodMembersMap[$c->Id_User] ?? '-');
            $checkerSummary[$name] = ($checkerSummary[$name] ?? 0) + 1;
        }
        arsort($checkerSummary);
        $summaryTotal = $periodChecks->count();

        $isMonthly = (bool) $month;
        $summaryLabel = $isMonthly
            ? Carbon::createFromFormat('Y-m', $month)->format('F Y')
            : Carbon::parse($date)->format('d M Y');

        return view('admins.checks.index', compact('checks', 'checkerList', 'checkerSummary', 'summaryTotal', 'summaryLabel', 'isMonthly'));
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

    public function search()
    {
        if (request()->ajax()) {
            $query = Check::with(['member', 'user', 'rack']);

            if ($statusFilter = request('statusFilter')) {
                if ($statusFilter === 'scan') {
                    $query->whereNull('Status_Check')->where('Auto_Check', 1);
                } elseif ($statusFilter === 'selesai') {
                    $query->whereNull('Status_Check')->where('Auto_Check', '!=', 1);
                } elseif ($statusFilter === 'pending') {
                    $query->whereNotNull('Status_Check');
                }
            }

            return DataTables::eloquent($query)
                ->editColumn('Time_Check', function ($c) {
                    return $c->Time_Check ? Carbon::parse($c->Time_Check)->format('Y-m-d H:i') : '-';
                })
                ->addColumn('Name_Item', function ($c) {
                    return optional($c->rack)->Name_Item_Rack ?? '-';
                })
                ->addColumn('Status_Display', function ($c) {
                    if (is_null($c->Status_Check)) {
                        if ($c->Auto_Check == 1) {
                            return '<span class="badge badge-info px-2 py-1">Scan</span>';
                        } else {
                            return '<span class="badge badge-secondary px-2 py-1">Selesai</span>';
                        }
                    } else {
                        $targetDate = Carbon::parse($c->Status_Check)->startOfDay();
                        $timeCheckDate = Carbon::parse($c->Time_Check)->startOfDay();
                        $today = Carbon::today();
                        
                        $daysDiff = $timeCheckDate->diffInDays($targetDate);
                        
                        if ($targetDate->equalTo($today)) {
                            return '<span class="badge badge-danger px-2 py-1">Hari Ini</span>';
                        } else {
                            $badgeText = $targetDate->format('d M Y');
                            if ($daysDiff == 1) $badgeClass = "badge-success";
                            elseif ($daysDiff == 2) $badgeClass = "badge-info";
                            elseif ($daysDiff == 3) $badgeClass = "badge-warning";
                            else $badgeClass = "badge-danger";

                            if ($targetDate->lessThan($today)) {
                                $badgeClass = "badge-secondary";
                            }
                            return '<span class="badge ' . $badgeClass . ' px-2 py-1">' . $badgeText . '</span>';
                        }
                    }
                })
                ->addColumn('checker_name', function ($c) {
                    if ($c->Is_User == 1) {
                        return (optional($c->user)->Name_User ?? optional($c->user)->Username_User ?? '-') . ' (Admin)';
                    }
                    return optional($c->member)->Name_Member ?? '-';
                })
                ->rawColumns(['Status_Display'])
                ->make(true);
        }

        // Get members and admins for filter
        $members = \App\Models\Member::where('Status_Non_Active', '!=', 1)
            ->orWhereNull('Status_Non_Active')
            ->orderBy('Name_Member')
            ->get(['Id_Member', 'Name_Member'])
            ->map(function ($m) {
                return (object) [
                    'id' => 'm_'.$m->Id_Member,
                    'name' => $m->Name_Member,
                ];
            });

        $users = \App\Models\User::where('Status_Non_Active', '!=', 1)
            ->orWhereNull('Status_Non_Active')
            ->orderBy('Username_User')
            ->get(['Id_User', 'Username_User', 'Name_User'])
            ->map(function ($u) {
                return (object) [
                    'id' => 'u_'.$u->Id_User,
                    'name' => ($u->Name_User ?? $u->Username_User) . ' (Admin)',
                ];
            });

        $people = $members->concat($users)->sortBy('name')->values();

        return view('admins.checks.search', compact('people'));
    }

    public function exportSearch(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $endDate = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        $statusFilter = $request->input('status');

        $query = Check::with(['member', 'user', 'rack'])
            ->whereDate('Time_Check', '>=', $startDate)
            ->whereDate('Time_Check', '<=', $endDate)
            ->orderBy('Time_Check', 'desc');

        if ($statusFilter) {
            if ($statusFilter === 'scan') {
                $query->whereNull('Status_Check')->where('Auto_Check', 1);
            } elseif ($statusFilter === 'selesai') {
                $query->whereNull('Status_Check')->where('Auto_Check', '!=', 1);
            } elseif ($statusFilter === 'pending') {
                $query->whereNotNull('Status_Check');
            }
        }

        $checks = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['No', 'Time Check', 'Area Check', 'Rack', 'Item', 'Name', 'Checker', 'Status'];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter('A1:H1');

        $row = 2;
        $no = 1;

        foreach ($checks as $c) {
            $checkerName = $c->Is_User == 1 
                ? ((optional($c->user)->Name_User ?? optional($c->user)->Username_User ?? '-') . ' (Admin)')
                : (optional($c->member)->Name_Member ?? '-');

            $statusLabel = '';
            if (is_null($c->Status_Check)) {
                $statusLabel = $c->Auto_Check == 1 ? 'Scan' : 'Selesai';
            } else {
                $statusLabel = Carbon::parse($c->Status_Check)->format('d M Y');
            }

            $sheet->fromArray([
                $no,
                $c->Time_Check ? Carbon::parse($c->Time_Check)->format('Y-m-d H:i') : '-',
                $c->Area_Check ?? '-',
                $c->Code_Rack,
                $c->Code_Item_Rack,
                optional($c->rack)->Name_Item_Rack ?? '-',
                $checkerName,
                $statusLabel
            ], null, 'A' . $row);

            $no++;
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Check_Search_{$startDate}_to_{$endDate}.xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
