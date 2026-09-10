<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Check;
use App\Models\Member;
use App\Models\Record;
use App\Models\BranchRecord;
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
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $people = $this->getPeople();

        // Initial setup for data arrays
        $requestsData = [];
        $recordsData = [];
        $branchRecordsData = [];
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
            $branchRecordsData[$person->id] = [
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

        $allBranchRecords = BranchRecord::whereMonth('Day_Branch_Record', $date->month)
            ->whereYear('Day_Branch_Record', $date->year)
            ->get();

        $monthlySummary = $this->getMonthlySummary($allRequests, $allRecords, $allBranchRecords, $daysInMonth);

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

        // Process Branch Records
        foreach ($allBranchRecords as $br) {
            $key = 'm_' . $br->Id_User;
            if (isset($branchRecordsData[$key])) {
                $day = (int) Carbon::parse($br->Day_Branch_Record)->format('d');
                $branchRecordsData[$key]['days'][$day]++;
                $branchRecordsData[$key]['total']++;
            }
        }

        // Sort by total descending
        uasort($requestsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        uasort($recordsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        uasort($branchRecordsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return view('admins.achievements.index', compact(
            'requestsData',
            'recordsData',
            'branchRecordsData',
            'monthlySummary',
            'month',
            'daysInMonth'
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
        $branchRecordsData = [];
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
            $branchRecordsData[$person->id] = [
                'name' => $person->name,
                'total' => 0,
                'days' => array_fill(1, $daysInMonth, 0),
            ];
        }

        $allRequests = RequestModel::whereMonth('Day_Request', $date->month)
            ->whereYear('Day_Request', $date->year)
            ->get();

        $allChecks = Check::whereMonth('Time_Check', $date->month)
            ->whereYear('Time_Check', $date->year)
            ->get();

        $allRecords = Record::whereMonth('Day_Record', $date->month)
            ->whereYear('Day_Record', $date->year)
            ->get();

        $allBranchRecords = BranchRecord::whereMonth('Day_Branch_Record', $date->month)
            ->whereYear('Day_Branch_Record', $date->year)
            ->get();

        foreach ($allRequests as $req) {
            $prefix = ($req->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix.$req->Id_User;
            if (isset($requestsData[$key])) {
                $day = (int) Carbon::parse($req->Day_Request)->format('d');
                $requestsData[$key]['days'][$day]++;
                $requestsData[$key]['total']++;
            }
        }

        foreach ($allChecks as $check) {
            $prefix = ($check->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix.$check->Id_User;
            if (isset($requestsData[$key])) {
                $day = (int) Carbon::parse($check->Time_Check)->format('d');
                $requestsData[$key]['days_check'][$day]++;
            }
        }

        foreach ($allRecords as $rec) {
            $prefix = ($rec->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix.$rec->Id_User;
            if (isset($recordsData[$key])) {
                $day = (int) Carbon::parse($rec->Day_Record)->format('d');
                $recordsData[$key]['days'][$day]++;
                $recordsData[$key]['total']++;
            }
        }

        foreach ($allBranchRecords as $br) {
            $key = 'm_' . $br->Id_User;
            if (isset($branchRecordsData[$key])) {
                $day = (int) Carbon::parse($br->Day_Branch_Record)->format('d');
                $branchRecordsData[$key]['days'][$day]++;
                $branchRecordsData[$key]['total']++;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Styling definitions
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4E73DF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        $contentStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D3E2'],
                ],
            ],
        ];

        // Title
        $sheet->setCellValue('A1', 'ACHIEVEMENT REPORT - '.$date->format('F Y'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Requests Table Header
        $sheet->setCellValue('A3', 'REQUESTS');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->setCellValue('A4', 'Name');
        $sheet->mergeCells('A4:A5');
        $sheet->setCellValue('B4', 'Total');
        $sheet->mergeCells('B4:B5');

        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($daysInMonth * 2));
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

                $sheet->setCellValue($col1.$row, $totalCount);
                $sheet->mergeCells($col1.$row.':'.$col1.($row + 1));

                $sheet->setCellValue($col2.$row, $reqCount);
                $sheet->setCellValue($col2.($row + 1), $chkCount);
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
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $i);
            $sheet->setCellValue($col.$row, $i);
        }

        $lastColLetterRec = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $daysInMonth);
        $sheet->getStyle('A'.$row.':'.$lastColLetterRec.$row)->applyFromArray($headerStyle);

        $row++;
        $startRowRec = $row;
        foreach ($recordsData as $personId => $data) {
            $sheet->setCellValue('A'.$row, $data['name']);
            $sheet->setCellValue('B'.$row, $data['total']);
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $i);
                $sheet->setCellValue($col.$row, $data['days'][$i]);
            }
            $row++;
        }
        $endRowRec = $row - 1;

        if ($endRowRec >= $startRowRec) {
            $sheet->getStyle('A'.$startRowRec.':A'.$endRowRec)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B'.$startRowRec.':'.$lastColLetterRec.$endRowRec)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A'.$startRowRec.':'.$lastColLetterRec.$endRowRec)->applyFromArray($contentStyle);
        }

        $row += 2;

        // Branch Record Table Header
        $sheet->setCellValue('A'.$row, 'BRANCH RECORDS');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A'.$row, 'Name');
        $sheet->setCellValue('B'.$row, 'Total');
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $i);
            $sheet->setCellValue($col.$row, $i);
        }

        $sheet->getStyle('A'.$row.':'.$lastColLetterRec.$row)->applyFromArray($headerStyle);

        $row++;
        $startRowBr = $row;
        foreach ($branchRecordsData as $personId => $data) {
            $sheet->setCellValue('A'.$row, $data['name']);
            $sheet->setCellValue('B'.$row, $data['total']);
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $i);
                $sheet->setCellValue($col.$row, $data['days'][$i]);
            }
            $row++;
        }
        $endRowBr = $row - 1;

        if ($endRowBr >= $startRowBr) {
            $sheet->getStyle('A'.$startRowBr.':A'.$endRowBr)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B'.$startRowBr.':'.$lastColLetterRec.$endRowBr)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A'.$startRowBr.':'.$lastColLetterRec.$endRowBr)->applyFromArray($contentStyle);
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

    private function getMonthlySummary($allRequests, $allRecords, $allBranchRecords, $daysInMonth)
    {
        $monthlySummary = [
            'days' => [],
            'totals' => [
                'request' => 0,
                'ready' => 0,
                'shipping' => 0,
                'design_change' => 0,
                'record' => 0,
                'branch_record' => 0,
            ],
        ];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $monthlySummary['days'][$i] = [
                'request' => 0,
                'ready' => 0,
                'shipping' => 0,
                'design_change' => 0,
                'record' => 0,
                'branch_record' => 0,
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

        foreach ($allBranchRecords as $br) {
            $day = (int) Carbon::parse($br->Day_Branch_Record)->format('d');
            if (isset($monthlySummary['days'][$day])) {
                $monthlySummary['days'][$day]['branch_record']++;
                $monthlySummary['totals']['branch_record']++;
            }
        }

        return $monthlySummary;
    }
}
