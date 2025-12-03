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
        $memberId = request('Id_User'); // ambil filter member kalau ada

        $query = RequestModel::whereDate('Day_Request', $date)
            ->with('member', 'record', 'rack')
            ->orderBy('Time_Request', 'desc');

        if ($memberId) {
            $query->where('Id_User', $memberId);
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
        $memberId = $request->input('Id_User');

        $query = RequestModel::whereDate('Day_Request', $date)
            ->with('member', 'record', 'rack')
            ->orderBy('Time_Request', 'desc');

        if ($memberId) {
            $query->where('Id_User', $memberId);
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
        $memberId = $request->input('Id_User');

        $query = RequestModel::whereDate('Day_Request', $date)
            ->with('member', 'record', 'rack')
            ->orderBy('Id_User')
            ->orderBy('Urgent_Request', 'desc')
            ->orderBy('Area_Request')
            ->orderBy('Time_Request');

        if ($memberId) {
            $query->where('Id_User', $memberId);
        }

        $requests = $query->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No', 'Time Request', 'Area', 'Rack', 'Sum Request', 'Urgenity', 'Item', 'Name',
            'Ready Status', 'Ready Stock', 'Time Record', 'Sum Record', 'Member Request', 
            'Member Record', 'Updated', 'Id'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

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

            $timeRequest = ($request->Day_Request ?? '') . " " . ($request->Time_Request ?? '');
            $timeRecord = ($request->record->Day_Record ?? '') . " " . ($request->record->Time_Record ?? '');

            $sheet->fromArray([
                $no,
                $timeRequest,
                $request->Area_Request ?? '',
                $request->Code_Rack,
                $request->Sum_Request,
                $request->Urgent_Request == 1 ? '✓' : '',
                $request->Code_Item_Rack,
                $request->rack->Name_Item_Rack ?? '',
                $request->Ready_Request !== null ? '1' : '',
                $request->Ready_Request ?? '',
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
        $request->validate([
            'ready_excel' => 'required|mimes:xlsx,xls'
        ]);

        $file = $request->file('ready_excel');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true); // index by column A, B, ...

        // Lewati header
        unset($rows[1]);

        $updatedCount = 0;

        foreach ($rows as $row) {
            // Asumsi: kolom 'Id' ada di kolom terakhir → cari key terakhir
            $idColumn = array_key_last($row);
            $idRequest = $row[$idColumn] ?? null;

            if (!$idRequest || !is_numeric($idRequest)) {
                continue;
            }

            // Cari kolom "Ready Status" → pastikan index-nya benar
            // Header: ..., 'Name', 'Ready Status', 'Ready Stock', ..., 'Id'
            // Kita cari posisi "Ready Status" berdasarkan header
            // Tapi untuk efisiensi, asumsikan kolom "Ready Status" di posisi ke-9 (I)
            // Atau ambil dari header baris pertama (jika perlu dinamis)

            // Alternatif: karena kita tahu strukturnya, ambil kolom ke-9 (I)
            $readyStatus = $row['I'] ?? ''; // I = kolom ke-9

            // Cek apakah ada centang (1)
            if (trim($readyStatus) !== '1') {
                continue;
            }

            // Ambil data request dari DB
            $requestModel = RequestModel::find($idRequest);

            if (!$requestModel) {
                continue;
            }

            // Jika Ready_Request sudah ada (not null), **jangan update**
            if ($requestModel->Ready_Request !== null) {
                continue;
            }

            // Update dengan waktu sekarang
            $requestModel->Ready_Request = Carbon::now();
            $requestModel->save();

            $updatedCount++;
        }

        return redirect()->back()->with('success', "Berhasil memperbarui $updatedCount data Ready Status.");
    }
}
