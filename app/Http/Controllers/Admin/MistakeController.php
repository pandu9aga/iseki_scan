<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mistake;
use App\Models\Request as RequestModel;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MistakeController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $mistakes = Mistake::with(['request.member'])
            ->whereMonth('Day_Mistake', $date->month)
            ->whereYear('Day_Mistake', $date->year)
            ->get();

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->get();
        $categories = ['telat request', 'telat supply', 'shipping', 'perubahan desain', 'lain-lain'];

        $reportData = [];
        foreach ($categories as $cat) {
            $catData = [];
            foreach ($members as $member) {
                $memberMistakes = $mistakes->filter(function ($m) use ($member, $cat) {
                    return $m->Category_Mistake === $cat && $m->PIC === $member->Name_Member;
                });

                $days = array_fill(1, $daysInMonth, 0);
                foreach ($memberMistakes as $m) {
                    $day = (int) Carbon::parse($m->Day_Mistake)->format('d');
                    $days[$day]++;
                }

                $catData[$member->Id_Member] = [
                    'name' => $member->Name_Member,
                    'total' => $memberMistakes->count(),
                    'days' => $days
                ];
            }
            
            // Sort each category by total descending
            uasort($catData, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });
            
            $reportData[$cat] = $catData;
        }

        // Prepare chart data (Mistakes per member per day - Cumulative)
        $chartData = [];
        $isCurrentMonth = ($date->year == Carbon::now()->year && $date->month == Carbon::now()->month);
        $currentDay = Carbon::now()->day;

        foreach ($members as $member) {
            $memberDays = array_fill(1, $daysInMonth, 0);
            $memberMistakes = $mistakes->filter(function ($m) use ($member) {
                return $m->PIC === $member->Name_Member;
            });

            if ($memberMistakes->count() > 0) {
                foreach ($memberMistakes as $m) {
                    $day = (int) Carbon::parse($m->Day_Mistake)->format('d');
                    $memberDays[$day]++;
                }

                // Accumulate mistake counts
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
                    'label' => $member->Name_Member . " (" . $memberMistakes->count() . ")",
                    'data' => $accumulatedData
                ];
            }
        }

        // Prepare Daily Total chart data (Daily sums across all members)
        $dailyTotalData = array_fill(1, $daysInMonth, 0);
        foreach ($mistakes as $m) {
            $day = (int) Carbon::parse($m->Day_Mistake)->format('d');
            $dailyTotalData[$day]++;
        }

        return view('admins.mistakes.index', compact('reportData', 'month', 'categories', 'daysInMonth', 'chartData', 'dailyTotalData'));
    }

    public function detail(Request $request)
    {
        $memberId = $request->member_id;
        $category = $request->category;
        $day = $request->day;
        $month = $request->month;

        $member = Member::findOrFail($memberId);
        
        $query = Mistake::with(['request.member', 'request.rack', 'request.record.member'])
            ->where('PIC', $member->Name_Member)
            ->where('Category_Mistake', $category);

        if ($day) {
            $date = Carbon::parse($month . '-' . $day)->format('Y-m-d');
            $query->where('Day_Mistake', $date);
            $titlePrefix = "Mistake Detail for " . $member->Name_Member . " on " . $date;
        } else {
            $dateObj = Carbon::parse($month);
            $query->whereMonth('Day_Mistake', $dateObj->month)
                ->whereYear('Day_Mistake', $dateObj->year);
            $titlePrefix = "Mistake Detail for " . $member->Name_Member . " in " . $dateObj->format('F Y');
        }

        $mistakes = $query->get();
        
        return view('admins.mistakes.detail', compact('mistakes', 'member', 'category', 'titlePrefix'));
    }

    public function add()
    {
        $pics = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')
            ->orderBy('Name_Member')
            ->get();
        
        $categories = ['telat request', 'telat supply', 'shipping', 'perubahan desain', 'lain-lain'];
        return view('admins.mistakes.add', compact('pics', 'categories'));
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
            'Category_Mistake' => 'required',
            'Day_Mistake' => 'required|date',
        ]);

        $category = $request->Category_Mistake;
        $manualDetail = null;
        if ($category === 'lain-lain') {
            $manualDetail = $request->Manual_Category_Detail;
        }

        Mistake::create([
            'Id_Request' => $request->Id_Request,
            'PIC' => $request->PIC,
            'Category_Mistake' => $category,
            'Manual_Category_Detail' => $manualDetail,
            'Day_Mistake' => $request->Day_Mistake,
        ]);

        return redirect()->route('mistake')->with('success', 'Mistake recorded successfully.');
    }

    public function export(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $mistakes = Mistake::with(['request.member'])
            ->whereMonth('Day_Mistake', $date->month)
            ->whereYear('Day_Mistake', $date->year)
            ->get();

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->get();
        $categories = ['telat request', 'telat supply', 'shipping', 'perubahan desain', 'lain-lain'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Mistake Daily Report - ' . $date->format('F Y'));
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

        foreach ($categories as $cat) {
            // Title for Category
            $sheet->setCellValue('A' . $row, strtoupper($cat));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $row++;

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
            $catData = [];
            foreach ($members as $member) {
                $memberMistakes = $mistakes->filter(function ($m) use ($member, $cat) {
                    return $m->Category_Mistake === $cat && $m->PIC === $member->Name_Member;
                });

                if ($memberMistakes->count() > 0) {
                    $days = array_fill(1, $daysInMonth, 0);
                    foreach ($memberMistakes as $m) {
                        $day = (int) Carbon::parse($m->Day_Mistake)->format('d');
                        $days[$day]++;
                    }

                    $catData[] = [
                        'name' => $member->Name_Member,
                        'total' => $memberMistakes->count(),
                        'days' => $days
                    ];
                }
            }

            // Sort by total descending
            usort($catData, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            // Write Data
            $startRow = $row;
            if (count($catData) > 0) {
                foreach ($catData as $data) {
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
                $sheet->setCellValue('A' . $row, 'No records found for this category.');
                $sheet->mergeCells('A' . $row . ':' . $lastColLetter . $row);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A' . $row . ':' . $lastColLetter . $row)->applyFromArray($contentStyle);
                $row++;
            }

            $row += 2; // Gap between category tables
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Mistake_Report_' . $month . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
