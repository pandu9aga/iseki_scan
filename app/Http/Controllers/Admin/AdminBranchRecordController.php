<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchRecord as BranchRecordModel;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdminBranchRecordController extends Controller
{
    /**
     * Tampilkan halaman Branch Record untuk Admin dengan ringkasan per member dan filter tanggal/bulan.
     */
    public function index(Request $request)
    {
        $date  = $request->input('date', Carbon::today()->format('Y-m-d'));
        $month = $request->input('month'); // format YYYY-MM
        $selectedMember = $request->input('member');
        $isMonthly = !empty($month);

        $summaryLabel = $isMonthly
            ? Carbon::parse($month)->locale('id')->isoFormat('MMMM YYYY')
            : Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM YYYY');

        // Filter waktu
        $timeFilter = function ($q) use ($date, $month) {
            if ($month) {
                $q->whereYear('Day_Branch_Record', substr($month, 0, 4))
                  ->whereMonth('Day_Branch_Record', (int)substr($month, 5, 2));
            } else {
                $q->whereDate('Day_Branch_Record', $date);
            }
        };

        // Query dasar
        $query = BranchRecordModel::with('member', 'rack');
        $timeFilter($query);

        if ($selectedMember) {
            $query->where('Id_User', $selectedMember);
        }

        $records = $query->orderBy('Day_Branch_Record', 'desc')
            ->orderBy('Time_Branch_Record', 'desc')
            ->get();

        // === Ringkasan per member (Siapa record berapa) ===
        $summaryQuery = BranchRecordModel::query();
        $timeFilter($summaryQuery);
        $allPeriodRecords = $summaryQuery->get();

        $memberIds = $allPeriodRecords->pluck('Id_User')->filter()->unique();
        $membersMap = $memberIds->isNotEmpty()
            ? Member::whereIn('Id_Member', $memberIds)->pluck('Name_Member', 'Id_Member')->toArray()
            : [];

        $checkerSummary = [];
        foreach ($allPeriodRecords as $r) {
            $name = $membersMap[$r->Id_User] ?? ('Member #' . $r->Id_User);
            $checkerSummary[$name] = ($checkerSummary[$name] ?? 0) + 1;
        }
        arsort($checkerSummary);
        $summaryTotal = $allPeriodRecords->count();

        // Dropdown member filter
        $memberList = Member::whereIn('Id_Member', $memberIds)->pluck('Name_Member', 'Id_Member')->toArray();
        asort($memberList);

        return view('admins.branch_records.index', compact(
            'records',
            'checkerSummary',
            'summaryTotal',
            'date',
            'month',
            'isMonthly',
            'summaryLabel',
            'memberList',
            'selectedMember'
        ));
    }

    /**
     * Export Excel Branch Record Admin.
     */
    public function export(Request $request)
    {
        $date  = $request->input('date', Carbon::today()->format('Y-m-d'));
        $month = $request->input('month');
        $selectedMember = $request->input('member');

        $query = BranchRecordModel::with('member', 'rack');

        if ($month) {
            $query->whereYear('Day_Branch_Record', substr($month, 0, 4))
                  ->whereMonth('Day_Branch_Record', (int)substr($month, 5, 2));
            $periodStr = $month;
        } else {
            $query->whereDate('Day_Branch_Record', $date);
            $periodStr = $date;
        }

        if ($selectedMember) {
            $query->where('Id_User', $selectedMember);
        }

        $records = $query->orderBy('Day_Branch_Record', 'asc')
            ->orderBy('Time_Branch_Record', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['No', 'Tanggal', 'Waktu', 'Kode Rak', 'Member / Operator', 'Updated At'];
        $sheet->fromArray([$headers], NULL, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17A2B8']]
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($records as $index => $r) {
            $sheet->fromArray([
                $index + 1,
                $r->Day_Branch_Record,
                $r->Time_Branch_Record,
                $r->Code_Rack,
                $r->display_name ?? ('Member #' . $r->Id_User),
                $r->Updated_At_Branch_Record ?? '-',
            ], NULL, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Admin_Branch_Record_" . $periodStr . ".xlsx";
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
