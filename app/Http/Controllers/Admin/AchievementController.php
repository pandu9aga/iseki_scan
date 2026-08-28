<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Check;
use App\Models\Member;
use App\Models\Record;
use App\Models\Request as RequestModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AchievementController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('date', Carbon::today()->format('Y-m-d'));
        $month = $request->input('month', Carbon::parse($selectedDate)->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        // Daily summary metrics
        $totalDailyRequests = RequestModel::whereDate('Day_Request', $selectedDate)->count();
        $totalDailyReady = RequestModel::whereDate('Day_Request', $selectedDate)->whereNotNull('Ready_Request')->count();
        $totalDailyShipping = RequestModel::whereDate('Day_Request', $selectedDate)->whereNotNull('Shipping_Request')->count();
        $totalDailyDesignChanges = RequestModel::whereDate('Day_Request', $selectedDate)->whereNotNull('Design_Changes_Request')->count();
        $totalDailyRecords = Record::whereDate('Day_Record', $selectedDate)->count();
        $formattedSelectedDate = Carbon::parse($selectedDate)->locale('en')->isoFormat('dddd, D-MMM-YY');

        $people = $this->getPeople();

        // Initial setup for data arrays
        $requestsData = [];
        $recordsData = [];
        foreach ($people as $person) {
            $requestsData[$person->id] = [
                'name' => $person->name,
                'total' => 0,
                'days' => array_fill(1, $daysInMonth, 0),
                'days_check' => array_fill(1, $daysInMonth, 0),
            ];
            $recordsData[$person->id] = [
                'name' => $person->name,
                'total' => 0,
                'days' => array_fill(1, $daysInMonth, 0),
            ];
        }

        // Fetch all data for the month in bulk
        $allRequests = RequestModel::whereMonth('Day_Request', $date->month)
            ->whereYear('Day_Request', $date->year)
            ->get();

        $allChecks = Check::whereMonth('Time_Check', $date->month)
            ->whereYear('Time_Check', $date->year)
            ->get();

        $allRecords = Record::whereMonth('Day_Record', $date->month)
            ->whereYear('Day_Record', $date->year)
            ->get();

        // Process Requests
        foreach ($allRequests as $req) {
            $prefix = ($req->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix.$req->Id_User;
            if (isset($requestsData[$key])) {
                $day = (int) Carbon::parse($req->Day_Request)->format('d');
                $requestsData[$key]['days'][$day]++;
                $requestsData[$key]['total']++;
            }
        }

        // Process Checks
        foreach ($allChecks as $check) {
            $prefix = ($check->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix.$check->Id_User;
            if (isset($requestsData[$key])) {
                $day = (int) Carbon::parse($check->Time_Check)->format('d');
                $requestsData[$key]['days_check'][$day]++;
            }
        }

        // Process Records
        foreach ($allRecords as $rec) {
            $prefix = ($rec->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix.$rec->Id_User;
            if (isset($recordsData[$key])) {
                $day = (int) Carbon::parse($rec->Day_Record)->format('d');
                $recordsData[$key]['days'][$day]++;
                $recordsData[$key]['total']++;
            }
        }

        // Sort by total descending
        uasort($requestsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        uasort($recordsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $monthlySummary = $this->getMonthlySummary($allRequests, $allRecords, $daysInMonth);

        return view('admins.achievements.index', compact(
            'requestsData',
            'recordsData',
            'monthlySummary',
            'month',
            'daysInMonth',
            'selectedDate',
            'formattedSelectedDate',
            'totalDailyRequests',
            'totalDailyReady',
            'totalDailyShipping',
            'totalDailyDesignChanges',
            'totalDailyRecords'
        ));
    }

    public function export(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $people = $this->getPeople();

        // Initial setup for data arrays
        $requestsData = [];
        $recordsData = [];
        foreach ($people as $person) {
            $requestsData[$person->id] = [
                'name' => $person->name,
                'total' => 0,
                'days' => array_fill(1, $daysInMonth, 0),
                'days_check' => array_fill(1, $daysInMonth, 0),
            ];
            $recordsData[$person->id] = [
                'name' => $person->name,
                'total' => 0,
                'days' => array_fill(1, $daysInMonth, 0),
            ];
        }

        // Fetch all data for the month in bulk
        $allRequests = RequestModel::whereMonth('Day_Request', $date->month)
            ->whereYear('Day_Request', $date->year)
            ->get();

        $allChecks = Check::whereMonth('Time_Check', $date->month)
            ->whereYear('Time_Check', $date->year)
            ->get();

        $allRecords = Record::whereMonth('Day_Record', $date->month)
            ->whereYear('Day_Record', $date->year)
            ->get();

        $monthlySummary = $this->getMonthlySummary($allRequests, $allRecords, $daysInMonth);

        // Process Requests
        foreach ($allRequests as $req) {
            $prefix = ($req->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix.$req->Id_User;
            if (isset($requestsData[$key])) {
                $day = (int) Carbon::parse($req->Day_Request)->format('d');
                $requestsData[$key]['days'][$day]++;
                $requestsData[$key]['total']++;
            }
        }

        // Process Checks
        foreach ($allChecks as $check) {
            $prefix = ($check->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix.$check->Id_User;
            if (isset($requestsData[$key])) {
                $day = (int) Carbon::parse($check->Time_Check)->format('d');
                $requestsData[$key]['days_check'][$day]++;
            }
        }

        // Process Records
        foreach ($allRecords as $rec) {
            $prefix = ($rec->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix.$rec->Id_User;
            if (isset($recordsData[$key])) {
                $day = (int) Carbon::parse($rec->Day_Record)->format('d');
                $recordsData[$key]['days'][$day]++;
                $recordsData[$key]['total']++;
            }
        }

        // Sort by total descending
        uasort($requestsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        uasort($recordsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $spreadsheet = new Spreadsheet;

        // -------------------------------------------------------------
        // Sheet 1: Daily Summary
        // -------------------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Daily Summary');

        // Header Title
        $sheet1->setCellValue('A1', 'Daily Summary - '.$date->format('F Y'));
        $sheet1->mergeCells('A1:F1');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $summaryHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4E73DF'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ];

        $summaryTotalStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3E6F0'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
        ];

        $summaryContentStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // Column headers
        $summaryHeaders = ['Date', 'Request', 'Ready', 'Shipping', 'Perubahan Desain', 'Record'];
        $sheet1->fromArray([$summaryHeaders], null, 'A3');
        $sheet1->getStyle('A3:F3')->applyFromArray($summaryHeaderStyle);

        // Header Background Colors per Column
        $headerColors = [
            'A3' => '5A5C69', // Date
            'B3' => 'E83E8C', // Request (Pink)
            'C3' => '1CC88A', // Ready
            'D3' => '36B9CC', // Shipping
            'E3' => 'F6C23E', // Perubahan Desain
            'F3' => '4E73DF', // Record (Navy Blue)
        ];
        foreach ($headerColors as $cell => $color) {
            $sheet1->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($color);
        }

        // Subheader TOTAL
        $subHeaderTotal = [
            'TOTAL',
            $monthlySummary['totals']['request'],
            $monthlySummary['totals']['ready'],
            $monthlySummary['totals']['shipping'],
            $monthlySummary['totals']['design_change'],
            $monthlySummary['totals']['record'],
        ];
        $sheet1->fromArray([$subHeaderTotal], null, 'A4');
        $sheet1->getStyle('A4:F4')->applyFromArray($summaryTotalStyle);
        $sheet1->getStyle('B4')->getFont()->getColor()->setRGB('E83E8C');
        $sheet1->getStyle('C4')->getFont()->getColor()->setRGB('1CC88A');
        $sheet1->getStyle('D4')->getFont()->getColor()->setRGB('36B9CC');
        $sheet1->getStyle('E4')->getFont()->getColor()->setRGB('F6C23E');
        $sheet1->getStyle('F4')->getFont()->getColor()->setRGB('4E73DF');

        // Daily Rows
        $sRow = 5;
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dayData = $monthlySummary['days'][$i];
            $sheet1->fromArray([
                $i,
                $dayData['request'],
                $dayData['ready'],
                $dayData['shipping'],
                $dayData['design_change'],
                $dayData['record'],
            ], null, 'A'.$sRow);
            $sheet1->getStyle('A'.$sRow.':F'.$sRow)->applyFromArray($summaryContentStyle);
            $sRow++;
        }

        // Footer TOTAL
        $sheet1->fromArray([$subHeaderTotal], null, 'A'.$sRow);
        $sheet1->getStyle('A'.$sRow.':F'.$sRow)->applyFromArray($summaryTotalStyle);
        $sheet1->getStyle('B'.$sRow)->getFont()->getColor()->setRGB('E83E8C');
        $sheet1->getStyle('C'.$sRow)->getFont()->getColor()->setRGB('1CC88A');
        $sheet1->getStyle('D'.$sRow)->getFont()->getColor()->setRGB('36B9CC');
        $sheet1->getStyle('E'.$sRow)->getFont()->getColor()->setRGB('F6C23E');
        $sheet1->getStyle('F'.$sRow)->getFont()->getColor()->setRGB('4E73DF');

        foreach (range('A', 'F') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // -------------------------------------------------------------
        // Sheet 2: Member Achievement
        // -------------------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Member Achievement');
        $sheet = $sheet2;

        // Header
        $sheet->setCellValue('A1', 'Achievement Report - '.$date->format('F Y'));
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($daysInMonth * 2));
        $sheet->mergeCells('A1:'.$lastColLetter.'1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Style arrays
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
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // Request Table Header
        $sheet->setCellValue('A3', 'REQUESTS');
        $sheet->getStyle('A3')->getFont()->setBold(true);

        $sheet->setCellValue('A4', 'Name');
        $sheet->setCellValue('B4', 'Total');
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $col1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($i * 2) - 1);
            $col2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($i * 2));
            $sheet->setCellValue($col1.'4', $i);
            $sheet->mergeCells($col1.'4:'.$col2.'4');
        }

        $sheet->getStyle('A4:'.$lastColLetter.'4')->applyFromArray($headerStyle);

        $row = 5;
        $startRowReq = $row;
        foreach ($requestsData as $personId => $data) {
            $sheet->setCellValue('A'.$row, $data['name']);
            $sheet->mergeCells('A'.$row.':A'.($row + 1));

            $sheet->setCellValue('B'.$row, $data['total']);
            $sheet->mergeCells('B'.$row.':B'.($row + 1));

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $col1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($i * 2) - 1);
                $col2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($i * 2));

                $reqCount = $data['days'][$i];
                $chkCount = $data['days_check'][$i];
                $totalCount = $reqCount + $chkCount;

                if ($totalCount > 0) {
                    $sheet->setCellValue($col1.$row, $totalCount);
                    $sheet->mergeCells($col1.$row.':'.$col1.($row + 1));

                    $sheet->setCellValue($col2.$row, $reqCount);
                    $sheet->setCellValue($col2.($row + 1), $chkCount);
                } else {
                    $sheet->setCellValue($col1.$row, 0);
                    $sheet->mergeCells($col1.$row.':'.$col1.($row + 1));
                    $sheet->setCellValue($col2.$row, 0);
                    $sheet->setCellValue($col2.($row + 1), 0);
                }
            }
            $row += 2;
        }
        $endRowReq = $row - 1;

        if ($endRowReq >= $startRowReq) {
            $sheet->getStyle('A'.$startRowReq.':A'.$endRowReq)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B'.$startRowReq.':'.$lastColLetter.$endRowReq)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A'.$startRowReq.':'.$lastColLetter.$endRowReq)->applyFromArray($contentStyle);
        }

        $row += 2;

        // Record Table Header
        $sheet->setCellValue('A'.$row, 'RECORDS');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A'.$row, 'Name');
        $sheet->setCellValue('B'.$row, 'Total');
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $col1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($i * 2) - 1);
            $col2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($i * 2));
            $sheet->setCellValue($col1.$row, $i);
            $sheet->mergeCells($col1.$row.':'.$col2.$row);
        }

        $sheet->getStyle('A'.$row.':'.$lastColLetter.$row)->applyFromArray($headerStyle);

        $row++;
        $startRowRec = $row;
        foreach ($recordsData as $personId => $data) {
            $sheet->setCellValue('A'.$row, $data['name']);
            $sheet->setCellValue('B'.$row, $data['total']);
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $col1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($i * 2) - 1);
                $col2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($i * 2));
                $sheet->setCellValue($col1.$row, $data['days'][$i]);
                $sheet->mergeCells($col1.$row.':'.$col2.$row);
            }
            $row++;
        }
        $endRowRec = $row - 1;

        if ($endRowRec >= $startRowRec) {
            $sheet->getStyle('A'.$startRowRec.':A'.$endRowRec)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B'.$startRowRec.':'.$lastColLetter.$endRowRec)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A'.$startRowRec.':'.$lastColLetter.$endRowRec)->applyFromArray($contentStyle);
        }

        // Autofit column width for name
        $sheet->getColumnDimension('A')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Achievement_Report_'.$month.'.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$fileName.'"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function getPeople()
    {
        $members = Member::where('Status_Non_Active', '!=', 1)
            ->orWhereNull('Status_Non_Active')
            ->orderBy('Name_Member')
            ->get(['Id_Member', 'Name_Member'])
            ->map(function ($m) {
                return (object) [
                    'id' => 'm_'.$m->Id_Member,
                    'name' => $m->Name_Member,
                    'original_id' => $m->Id_Member,
                    'type' => 'member',
                ];
            });

        $users = User::where('Status_Non_Active', '!=', 1)
            ->orWhereNull('Status_Non_Active')
            ->orderBy('Name_User')
            ->get(['Id_User', 'Name_User'])
            ->map(function ($u) {
                return (object) [
                    'id' => 'u_'.$u->Id_User,
                    'name' => $u->Name_User,
                    'original_id' => $u->Id_User,
                    'type' => 'user',
                ];
            });

        return $members->concat($users)->sortBy('name')->values();
    }

    private function getMonthlySummary($allRequests, $allRecords, $daysInMonth)
    {
        $monthlySummary = [
            'days' => [],
            'totals' => [
                'request' => 0,
                'ready' => 0,
                'shipping' => 0,
                'design_change' => 0,
                'record' => 0,
            ],
        ];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $monthlySummary['days'][$i] = [
                'request' => 0,
                'ready' => 0,
                'shipping' => 0,
                'design_change' => 0,
                'record' => 0,
            ];
        }

        foreach ($allRequests as $req) {
            $day = (int) Carbon::parse($req->Day_Request)->format('d');
            if (isset($monthlySummary['days'][$day])) {
                $monthlySummary['days'][$day]['request']++;
                $monthlySummary['totals']['request']++;

                if ($req->Ready_Request !== null) {
                    $monthlySummary['days'][$day]['ready']++;
                    $monthlySummary['totals']['ready']++;
                }
                if ($req->Shipping_Request !== null) {
                    $monthlySummary['days'][$day]['shipping']++;
                    $monthlySummary['totals']['shipping']++;
                }
                if ($req->Design_Changes_Request !== null) {
                    $monthlySummary['days'][$day]['design_change']++;
                    $monthlySummary['totals']['design_change']++;
                }
            }
        }

        foreach ($allRecords as $rec) {
            $day = (int) Carbon::parse($rec->Day_Record)->format('d');
            if (isset($monthlySummary['days'][$day])) {
                $monthlySummary['days'][$day]['record']++;
                $monthlySummary['totals']['record']++;
            }
        }

        return $monthlySummary;
    }
}
