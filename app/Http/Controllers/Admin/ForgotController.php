<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Forgot;
use App\Models\Request as RequestModel;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ForgotController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $forgots = Forgot::with(['request.member'])
            ->whereMonth('Day_Forgot', $date->month)
            ->whereYear('Day_Forgot', $date->year)
            ->get();

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->get();

        $reportData = [];
        foreach ($members as $member) {
            $memberForgots = $forgots->filter(function ($f) use ($member) {
                return $f->PIC === $member->Name_Member;
            });

            $days = array_fill(1, $daysInMonth, 0);
            foreach ($memberForgots as $f) {
                $day = (int) Carbon::parse($f->Day_Forgot)->format('d');
                $days[$day]++;
            }

            $reportData[$member->Id_Member] = [
                'name' => $member->Name_Member,
                'total' => $memberForgots->count(),
                'days' => $days
            ];
        }

        // Sort by total descending
        uasort($reportData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        // Prepare chart data (Forgots per member per day - Cumulative)
        $chartData = [];
        $isCurrentMonth = ($date->year == Carbon::now()->year && $date->month == Carbon::now()->month);
        $currentDay = Carbon::now()->day;

        foreach ($members as $member) {
            $memberDays = array_fill(1, $daysInMonth, 0);
            $memberForgots = $forgots->filter(function ($f) use ($member) {
                return $f->PIC === $member->Name_Member;
            });

            if ($memberForgots->count() > 0) {
                foreach ($memberForgots as $f) {
                    $day = (int) Carbon::parse($f->Day_Forgot)->format('d');
                    $memberDays[$day]++;
                }

                $cumulative = 0;
                $accumulatedData = [];
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    if ($isCurrentMonth && $i > $currentDay) {
                        $accumulatedData[] = null;
                    } else {
                        $cumulative += $memberDays[$i];
                        $accumulatedData[] = $cumulative;
                    }
                }

                $chartData[] = [
                    'label' => $member->Name_Member . " (" . $memberForgots->count() . ")",
                    'data' => $accumulatedData
                ];
            }
        }

        // Prepare Daily Total chart data (Daily sums across all members)
        $dailyTotalData = array_fill(1, $daysInMonth, 0);
        foreach ($forgots as $f) {
            $day = (int) Carbon::parse($f->Day_Forgot)->format('d');
            $dailyTotalData[$day]++;
        }

        return view('admins.forgots.index', compact('reportData', 'month', 'daysInMonth', 'chartData', 'dailyTotalData'));
    }

    public function detail(Request $request)
    {
        $memberId = $request->member_id;
        $day = $request->day;
        $month = $request->month;

        $member = Member::findOrFail($memberId);
        
        $query = Forgot::with(['request.member', 'request.rack', 'request.record.member'])
            ->where('PIC', $member->Name_Member);

        if ($day) {
            $date = Carbon::parse($month . '-' . $day)->format('Y-m-d');
            $query->where('Day_Forgot', $date);
            $titlePrefix = "Forgot Detail for " . $member->Name_Member . " on " . $date;
        } else {
            $dateObj = Carbon::parse($month);
            $query->whereMonth('Day_Forgot', $dateObj->month)
                ->whereYear('Day_Forgot', $dateObj->year);
            $titlePrefix = "Forgot Detail for " . $member->Name_Member . " in " . $dateObj->format('F Y');
        }

        $forgots = $query->get();
        
        return view('admins.forgots.detail', compact('forgots', 'member', 'titlePrefix'));
    }

    public function add()
    {
        $pics = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')
            ->orderBy('Name_Member')
            ->get();
        
        return view('admins.forgots.add', compact('pics'));
    }

    public function getLatestRequest(Request $request)
    {
        $codeRack = $request->code_rack;
        $latest = RequestModel::with(['member', 'record.member', 'rack'])
            ->where('Code_Rack', $codeRack)
            ->orderBy('Day_Request', 'desc')
            ->orderBy('Time_Request', 'desc')
            ->first();

        if ($latest) {
            return response()->json([
                'success' => true,
                'data' => [
                    'Id_Request' => $latest->Id_Request,
                    'Code_Item_Rack' => $latest->Code_Item_Rack,
                    'Name_Item' => optional($latest->rack)->Name_Item_Rack,
                    'Time_Request' => $latest->Day_Request . ' ' . $latest->Time_Request,
                    'Sum_Request' => $latest->Sum_Request,
                    'Status_Ready' => $latest->Ready_Request,
                    'Member_Request' => optional($latest->member)->Name_Member,
                    'Time_Record' => $latest->record ? $latest->record->Day_Record . ' ' . $latest->record->Time_Record : '-',
                    'Member_Record' => $latest->record && $latest->record->member ? $latest->record->member->Name_Member : '-',
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No request found for this rack code.']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'Id_Request' => 'required',
            'PIC' => 'required',
            'Day_Forgot' => 'required|date',
        ]);

        Forgot::create([
            'Id_Request' => $request->Id_Request,
            'PIC' => $request->PIC,
            'Day_Forgot' => $request->Day_Forgot,
        ]);

        return redirect()->route('forgot')->with('success', 'Forgot record saved successfully.');
    }

    public function export(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $forgots = Forgot::with(['request.member'])
            ->whereMonth('Day_Forgot', $date->month)
            ->whereYear('Day_Forgot', $date->year)
            ->get();

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Forgot Daily Report - ' . $date->format('F Y'));
        $sheet->mergeCells('A1:AJ1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row = 3;
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($daysInMonth + 2);

        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ];

        $contentStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ];

        // Table Header
        $sheet->setCellValue('A' . $row, 'Name');
        $sheet->setCellValue('B' . $row, 'Total');
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
            $sheet->setCellValue($col . $row, $i);
        }
        $sheet->getStyle('A' . $row . ':' . $lastColLetter . $row)->applyFromArray($headerStyle);
        $row++;

        // Data Preparation & Sorting
        $reportData = [];
        foreach ($members as $member) {
            $memberForgots = $forgots->filter(function ($f) use ($member) {
                return $f->PIC === $member->Name_Member;
            });

            if ($memberForgots->count() > 0) {
                $days = array_fill(1, $daysInMonth, 0);
                foreach ($memberForgots as $f) {
                    $day = (int) Carbon::parse($f->Day_Forgot)->format('d');
                    $days[$day]++;
                }

                $reportData[] = [
                    'name' => $member->Name_Member,
                    'total' => $memberForgots->count(),
                    'days' => $days
                ];
            }
        }

        // Sort by total descending
        usort($reportData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        // Write Data
        $startRow = $row;
        if (count($reportData) > 0) {
            foreach ($reportData as $data) {
                $sheet->setCellValue('A' . $row, $data['name']);
                $sheet->setCellValue('B' . $row, $data['total']);
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                    $sheet->setCellValue($col . $row, $data['days'][$i] ?: '-');
                }
                $row++;
            }
            $endRow = $row - 1;
            $sheet->getStyle('A' . $startRow . ':A' . $endRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B' . $startRow . ':' . $lastColLetter . $endRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $startRow . ':' . $lastColLetter . $endRow)->applyFromArray($contentStyle);
        } else {
            $sheet->setCellValue('A' . $row, 'No records found.');
            $sheet->mergeCells('A' . $row . ':' . $lastColLetter . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $row . ':' . $lastColLetter . $row)->applyFromArray($contentStyle);
            $row++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Forgot_Report_' . $month . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
