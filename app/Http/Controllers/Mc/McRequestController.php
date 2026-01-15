<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;
use App\Models\Member;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;

class McRequestController extends Controller
{
    public function index()
    {
        $date = Carbon::today();
        $dateForInput = $date->format('Y-m-d');
        $memberIds = request('Id_User', []); // ← sekarang array

        $query = RequestModel::whereDate('Day_Request', $date)
            ->with('member', 'record', 'rack')
            ->orderBy('Time_Request', 'desc');

        if (!empty($memberIds)) {
            $query->whereIn('Id_User', $memberIds); // ← whereIn, bukan where
        }

        $requests = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequest = $requests->count();
        $correct = $requests->where('Correctness_Request', 1)->count();
        $incorrect = $totalRequest - $correct;

        $members = Member::orderBy('Name_Member')->get();

        return view('mcs.requests.index', compact(
            'requests', 'totalRequest', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput', 'members'
        ));
    }

    public function submit(Request $request)
    {
        $date = $request->input('Day_Request');
        $dateForInput = Carbon::parse($date)->format('Y-m-d');
        $memberIds = $request->input('Id_User', []); // ← array

        $query = RequestModel::whereDate('Day_Request', $date)
            ->with('member', 'record', 'rack')
            ->orderBy('Time_Request', 'desc');

        if (!empty($memberIds)) {
            $query->whereIn('Id_User', $memberIds);
        }

        $requests = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequest = $requests->count();
        $correct = $requests->where('Correctness_Request', 1)->count();
        $incorrect = $totalRequest - $correct;

        $members = Member::orderBy('Name_Member')->get();

        return view('mcs.requests.index', compact(
            'requests', 'totalRequest', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput', 'members'
        ));
    }

    public function export(Request $request)
    {
        $date = Carbon::parse($request->input('Day_Request_Hidden'))->format('Y-m-d');
        $memberIds = $request->input('Id_User', []); // ← array

        $query = RequestModel::whereDate('Day_Request', $date)
            ->with('member', 'record', 'rack')
            ->orderBy('Id_User')
            ->orderBy('Urgent_Request', 'desc')
            ->orderBy('Area_Request')
            ->orderBy('Time_Request');

        if (!empty($memberIds)) {
            $query->whereIn('Id_User', $memberIds);
        }

        $requests = $query->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No', 'Time Request', 'Area', 'Rack', 'Sum Request', 'Urgenity', 'Item', 'Name',
            "1=Ready,2=Ship,\n3=Prod,4=Design", // ← \n = line break
            'Ready Stock', 'Time Record', 'Sum Record', 'Member Request', 
            'Member Record', 'Updated', 'Id'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:P1')->getAlignment()->setWrapText(true);

        $sheet->setAutoFilter(
            $sheet->calculateWorksheetDimension() // otomatis dari A1 sampai kolom terakhir
        );

        // Isi data
        $row = 2;
        $lastUser = null;
        $no = 1;

        foreach ($requests as $index => $request) {
            // Reset nomor & kasih spasi kalau ganti user
            if ($lastUser !== null && $lastUser != $request->Id_User) {
                $sheet->fromArray(
                    array_fill(0, 12, '-'), // 12 kolom sesuai header
                    null,
                    'A' . $row
                );
                $row++;
                $no = 1; // reset nomor
            }

            // Di dalam foreach ($requests as $request)
            $readyDisplay = [];

            if ($request->Ready_Request) {
                $readyDisplay[] = 'Ready: ' . $request->Ready_Request;
            }
            if ($request->Shipping_Request) {
                $readyDisplay[] = 'Shipping: ' . $request->Shipping_Request;
            }
            if ($request->Production_Area_Request) {
                $readyDisplay[] = 'Production: ' . $request->Production_Area_Request;
            }
            if ($request->Design_Changes_Request) {
                $readyDisplay[] = 'Design: ' . $request->Design_Changes_Request;
            }

            $readyStockDisplay = implode(' | ', $readyDisplay);

            $timeRequest = ($request->Day_Request ?? '') . " " . ($request->Time_Request ?? '');
            $timeRecord = ($request->record->Day_Record ?? '') . " " . ($request->record->Time_Record ?? '');

            $statusCode = '';
            if ($request->Ready_Request !== null) {
                $statusCode = '1';
            } elseif ($request->Shipping_Request !== null) {
                $statusCode = '2';
            } elseif ($request->Production_Area_Request !== null) {
                $statusCode = '3';
            } elseif ($request->Design_Changes_Request !== null) {
                $statusCode = '4';
            }

            $sheet->fromArray([
                $no,
                $timeRequest,
                $request->Area_Request ?? '',
                $request->Code_Rack,
                $request->Sum_Request,
                $request->Urgent_Request == 1 ? '✓' : '',
                $request->Code_Item_Rack,
                $request->rack->Name_Item_Rack ?? '',
                $statusCode,
                $readyStockDisplay,
                $timeRecord,
                optional($request->record)->Sum_Record ?? '',
                $request->member->Name_Member ?? '',
                optional($request->record)->member->Name_Member ?? '',
                $request->Updated_At_Request,
                $request->Id_Request,
            ], null, 'A' . $row);

            $lastUser = $request->Id_User;
            $no++;
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $columnsToCenter = ['E', 'F', 'I', 'L'];
            foreach ($columnsToCenter as $col) {
                $range = $col . '2:' . $col . $lastRow;
                $sheet->getStyle($range)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
        }

        // 🔑 Auto size kolom
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan ke file
        $fileName = "Request_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function uploadReady(Request $request)
    {
        $request->validate(['ready_excel' => 'required|mimes:xlsx,xls']);

        $file = $request->file('ready_excel');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        unset($rows[1]); // skip header

        $updatedCount = 0;

        foreach ($rows as $row) {
            $idColumn = array_key_last($row);
            $idRequest = $row[$idColumn] ?? null;

            if (!$idRequest || !is_numeric($idRequest)) {
                continue;
            }

            $requestModel = RequestModel::find($idRequest);
            if (!$requestModel) {
                continue;
            }

            // 🔑 Cek: apakah SUDAH ADA salah satu status terisi?
            $anyStatusFilled = 
                $requestModel->Ready_Request !== null ||
                $requestModel->Shipping_Request !== null ||
                $requestModel->Production_Area_Request !== null ||
                $requestModel->Design_Changes_Request !== null;

            // ❌ Jika sudah ada yang terisi, LEWATI (jangan update apapun)
            if ($anyStatusFilled) {
                continue;
            }

            // 🟢 Hanya lanjut jika BELUM ADA SATU PUN yang terisi
            $now = Carbon::now();
            $readyStatus = trim($row['I'] ?? '');

            switch ($readyStatus) {
                case '1':
                    $requestModel->Ready_Request = $now;
                    break;
                case '2':
                    $requestModel->Shipping_Request = $now;
                    break;
                case '3':
                    $requestModel->Production_Area_Request = $now;
                    break;
                case '4':
                    $requestModel->Design_Changes_Request = $now;
                    break;
                default:
                    continue 2; // skip row ini jika status tidak valid
            }

            $requestModel->save();
            $updatedCount++;
        }

        return redirect()->back()->with('success', "Berhasil memperbarui $updatedCount data Ready Status.");
    }
}
