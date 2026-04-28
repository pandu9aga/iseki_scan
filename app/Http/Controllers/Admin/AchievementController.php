<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $people = $this->getPeople();

        $requestsData = [];
        $recordsData = [];

        foreach ($people as $person) {
            // Requests
            $userRequests = RequestModel::where('Id_User', $person->original_id)
                ->where('Is_User', $person->type == 'user' ? 1 : 0)
                ->whereMonth('Day_Request', $date->month)
                ->whereYear('Day_Request', $date->year)
                ->get();

            $daysReq = array_fill(1, $daysInMonth, 0);
            foreach ($userRequests as $req) {
                $day = (int) Carbon::parse($req->Day_Request)->format('d');
                $daysReq[$day]++;
            }

            $requestsData[$person->id] = [
                'name' => $person->name,
                'total' => $userRequests->count(),
                'days' => $daysReq,
            ];

            // Records
            $userRecords = Record::where('Id_User', $person->original_id)
                ->where('Is_User', $person->type == 'user' ? 1 : 0)
                ->whereMonth('Day_Record', $date->month)
                ->whereYear('Day_Record', $date->year)
                ->get();

            $daysRec = array_fill(1, $daysInMonth, 0);
            foreach ($userRecords as $rec) {
                $day = (int) Carbon::parse($rec->Day_Record)->format('d');
                $daysRec[$day]++;
            }

            $recordsData[$person->id] = [
                'name' => $person->name,
                'total' => $userRecords->count(),
                'days' => $daysRec,
            ];
        }

        // Sort by total descending
        uasort($requestsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        uasort($recordsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return view('admins.achievements.index', compact('requestsData', 'recordsData', 'month', 'daysInMonth'));
    }

    public function export(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month);
        $daysInMonth = $date->daysInMonth;

        $people = $this->getPeople();

        $requestsData = [];
        $recordsData = [];

        foreach ($people as $person) {
            // Requests
            $userRequests = RequestModel::where('Id_User', $person->original_id)
                ->where('Is_User', $person->type == 'user' ? 1 : 0)
                ->whereMonth('Day_Request', $date->month)
                ->whereYear('Day_Request', $date->year)
                ->get();

            $daysReq = array_fill(1, $daysInMonth, 0);
            foreach ($userRequests as $req) {
                $day = (int) Carbon::parse($req->Day_Request)->format('d');
                $daysReq[$day]++;
            }

            $requestsData[$person->id] = [
                'name' => $person->name,
                'total' => $userRequests->count(),
                'days' => $daysReq,
            ];

            // Records
            $userRecords = Record::where('Id_User', $person->original_id)
                ->where('Is_User', $person->type == 'user' ? 1 : 0)
                ->whereMonth('Day_Record', $date->month)
                ->whereYear('Day_Record', $date->year)
                ->get();

            $daysRec = array_fill(1, $daysInMonth, 0);
            foreach ($userRecords as $rec) {
                $day = (int) Carbon::parse($rec->Day_Record)->format('d');
                $daysRec[$day]++;
            }

            $recordsData[$person->id] = [
                'name' => $person->name,
                'total' => $userRecords->count(),
                'days' => $daysRec,
            ];
        }

        // Sort by total descending
        uasort($requestsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        uasort($recordsData, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Achievement Report - '.$date->format('F Y'));
        $sheet->mergeCells('A1:AJ1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($daysInMonth + 2);

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
        ];

        // Request Table Header
        $sheet->setCellValue('A3', 'REQUESTS');
        $sheet->getStyle('A3')->getFont()->setBold(true);

        $sheet->setCellValue('A4', 'Name');
        $sheet->setCellValue('B4', 'Total');
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
            $sheet->setCellValue($col.'4', $i);
        }

        $sheet->getStyle('A4:'.$lastColLetter.'4')->applyFromArray($headerStyle);

        $row = 5;
        $startRowReq = $row;
        foreach ($requestsData as $personId => $data) {
            $sheet->setCellValue('A'.$row, $data['name']);
            $sheet->setCellValue('B'.$row, $data['total']);
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                $sheet->setCellValue($col.$row, $data['days'][$i]);
            }
            $row++;
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
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
            $sheet->setCellValue($col.$row, $i);
        }

        $sheet->getStyle('A'.$row.':'.$lastColLetter.$row)->applyFromArray($headerStyle);

        $row++;
        $startRowRec = $row;
        foreach ($recordsData as $personId => $data) {
            $sheet->setCellValue('A'.$row, $data['name']);
            $sheet->setCellValue('B'.$row, $data['total']);
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                $sheet->setCellValue($col.$row, $data['days'][$i]);
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
}
