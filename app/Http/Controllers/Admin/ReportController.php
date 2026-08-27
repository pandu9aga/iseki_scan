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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index()
    {
        $date = Carbon::today();
        $dateForInput = $date->format('Y-m-d');
        $memberId = request('Id_User'); // ambil filter member kalau ada

        $query = Record::whereDate('Day_Record', $date)
            ->orderBy('Time_Record', 'desc')
            ->with('member', 'request');

        if ($memberId) {
            $this->applyMemberFilter($query, $memberId);
        }

        $records = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();
        $date = Carbon::parse($date)->isoFormat('YYYY-MM-DD');

        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        $members = $this->getPeople();

        return view('admins.reports.index', compact(
            'records', 'totalRecords', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput', 'members'
        ));
    }

    public function submit(Request $request)
    {
        $date = $request->input('Day_Record');
        $dateForInput = Carbon::parse($date)->format('Y-m-d');
        $memberId = $request->input('Id_User');

        $query = Record::whereDate('Day_Record', $date)
            ->orderBy('Time_Record', 'desc')
            ->with('member', 'request');

        if ($memberId) {
            $this->applyMemberFilter($query, $memberId);
        }

        $records = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRecords = $records->count();

        $correct = $records->filter(function ($record) {
            return $record->Correctness_Record == 1;
        })->count();
        $incorrect = $records->count() - $correct;

        $members = $this->getPeople();

        return view('admins.reports.index', compact(
            'records', 'totalRecords', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput', 'members'
        ));
    }

    public function export(Request $request)
    {
        $date = Carbon::parse($request->input('Day_Record_Hidden'))->format('Y-m-d');
        $memberId = $request->input('Id_User');

        // Ambil data dengan join requests supaya bisa order by Area_Request
        $query = Record::whereDate('records.Day_Record', $date)
            ->with('member', 'request', 'rack')
            ->leftJoin('requests', 'records.Id_Request', '=', 'requests.Id_Request')
            ->select('records.*') // supaya tetap model Record
            ->orderBy('records.Id_User', 'asc')
            ->orderByRaw("COALESCE(requests.Area_Request, '') asc") // null duluan
            ->orderBy('records.Day_Record', 'asc')
            ->orderBy('records.Time_Record', 'asc');

        // prefix table name supaya tidak ambiguous
        if ($memberId) {
            $this->applyMemberFilter($query, $memberId, 'records.');
        }

        $records = $query->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No', 'Time Record', 'Area', 'Rack', 'Sum Record',
            'Item', 'Name', 'Correctness', 'Time Request',
            'Sum Request', 'Sum Stock', 'Estimation Date', 'Member Request', 'Member Record', 'Updated',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ];
        $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        // Isi data
        $row = 2;
        $lastUser = null;
        $no = 1;

        foreach ($records as $record) {
            // jika user berubah -> tambah satu baris pemisah yang berisi '-' lalu reset nomor
            if ($lastUser !== null && $record->Id_User != $lastUser) {
                $sheet->fromArray(array_fill(0, count($headers), '-'), null, 'A'.$row);
                $row++;
                $no = 1;
            }

            $correctness = $record->Correctness_Record == 1 ? 'Correct' : 'Incorrect';
            $timeRecord = ($record->Day_Record ?? '').' '.($record->Time_Record ?? '');
            $timeRequest = (optional($record->request)->Day_Request ?? '').' '.(optional($record->request)->Time_Request ?? '');

            $statusCode = '';
            if (optional($record->request)->Ready_Request !== null) {
                $statusCode = '1';
            } elseif (optional($record->request)->Shipping_Request !== null) {
                $statusCode = '2';
            } elseif (optional($record->request)->Production_Area_Request !== null) {
                $statusCode = '3';
            } elseif (optional($record->request)->Design_Changes_Request !== null) {
                $statusCode = '4';
            }

            $sumStockDisplay = '';
            if ($statusCode == '2' || $statusCode == '4') {
                $sumStockDisplay = optional($record->request)->Stock_Shipping ?? '';
            } else {
                $sumStockDisplay = optional($record->request)->Sum_Stock ?? '';
            }

            $estimationDisplay = '';
            if (optional($record->request)->Estimation_Stock) {
                $estimationDisplay = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    Carbon::parse(optional($record->request)->Estimation_Stock)
                );
            }

            $sheet->fromArray([
                $no,
                $timeRecord,
                optional($record->request)->Area_Request ?? '',
                $record->Code_Rack,
                $record->Sum_Record,
                $record->Code_Item_Rack,
                $record->rack->Name_Item_Rack ?? '',
                $correctness,
                $timeRequest,
                optional($record->request)->Sum_Request ?? '',
                $sumStockDisplay,
                $estimationDisplay,
                optional($record->request)->display_name ?? '',
                $record->display_name,
                $record->Updated_At_Record ?? '',
            ], null, 'A'.$row);

            // warna Correct/Incorrect
            $correctnessCell = 'H'.$row;
            $sheet->getStyle($correctnessCell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $correctness === 'Correct' ? '008000' : 'FF0000'],
                ],
            ]);

            $lastUser = $record->Id_User;
            $no++;
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle('L2:L'.$lastRow)->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        }

        // Auto-size kolom
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan & download
        $fileName = 'Record_'.$date.'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/'.$fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function readyWaiting(Request $request)
    {
        $memberId = $request->input('Id_User');

        $query = RequestModel::with(['member', 'user', 'rack'])
            ->whereNotNull('Ready_Request')
            ->where(function ($q) {
                $q->where('Status_Request', '!=', 'Done')
                  ->orWhereNull('Status_Request');
            })
            ->orderBy('Ready_Request', 'desc');

        if ($memberId) {
            $this->applyMemberFilter($query, $memberId);
        }

        $requests = $query->get();
        $totalRequests = $requests->count();
        $members = $this->getPeople();

        return view('admins.reports.ready_waiting', compact(
            'requests', 'totalRequests', 'members'
        ));
    }

    public function readyWaitingExport(Request $request)
    {
        $memberId = $request->input('Id_User');

        $query = RequestModel::with(['member', 'user', 'rack'])
            ->whereNotNull('Ready_Request')
            ->where(function ($q) {
                $q->where('Status_Request', '!=', 'Done')
                  ->orWhereNull('Status_Request');
            })
            ->orderBy('Ready_Request', 'desc');

        if ($memberId) {
            $this->applyMemberFilter($query, $memberId);
        }

        $requests = $query->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No', 'Time Req', 'Rack', 'Item', 'Name Part', 'Time Ready', 'PIC Req',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        // Isi data
        $row = 2;
        $no = 1;

        foreach ($requests as $req) {
            $timeReq = trim(($req->Day_Request ?? '').' '.($req->Time_Request ?? ''));
            $timeReady = $req->Ready_Request ?? '';
            $namePart = optional($req->rack)->Name_Item_Rack ?? '';
            $picReq = $req->display_name ?? '';

            $sheet->fromArray([
                $no,
                $timeReq,
                $req->Code_Rack ?? '',
                $req->Code_Item_Rack ?? '',
                $namePart,
                $timeReady,
                $picReq,
            ], null, 'A'.$row);

            $no++;
            $row++;
        }

        // Auto-size kolom
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan & download
        $fileName = 'Ready_Waiting_Requests_'.Carbon::today()->format('Y-m-d').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/'.$fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
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

    private function applyMemberFilter($query, $memberId, $prefix = '')
    {
        if (strpos($memberId, 'u_') === 0) {
            $originalId = substr($memberId, 2);
            $query->where($prefix.'Id_User', $originalId)->where($prefix.'Is_User', 1);
        } else {
            $originalId = strpos($memberId, 'm_') === 0 ? substr($memberId, 2) : $memberId;
            $query->where($prefix.'Id_User', $originalId)
                  ->where(function($q) use ($prefix) {
                      $q->where($prefix.'Is_User', 0)->orWhereNull($prefix.'Is_User');
                  });
        }
    }
}
