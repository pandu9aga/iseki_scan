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

        $statusFilter = request('statusFilter');
        if ($statusFilter) {
            switch ($statusFilter) {
                case 'ready':
                    $query->whereNotNull('Ready_Request');
                    break;
                case 'shipping':
                    $query->whereNotNull('Shipping_Request');
                    break;
                case 'production':
                    $query->whereNotNull('Production_Area_Request');
                    break;
                case 'design_change':
                    $query->whereNotNull('Design_Changes_Request');
                    break;
            }
        }

        $requests = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequest = $requests->count();
        $correct = $requests->where('Correctness_Request', 1)->count();
        $incorrect = $totalRequest - $correct;

        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get();

        return view('mcs.requests.index', compact(
            'requests',
            'totalRequest',
            'correct',
            'incorrect',
            'formattedDate',
            'date',
            'dateForInput',
            'members'
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

        $statusFilter = $request->input('statusFilter');
        if ($statusFilter) {
            switch ($statusFilter) {
                case 'ready':
                    $query->whereNotNull('Ready_Request');
                    break;
                case 'shipping':
                    $query->whereNotNull('Shipping_Request');
                    break;
                case 'production':
                    $query->whereNotNull('Production_Area_Request');
                    break;
                case 'design_change':
                    $query->whereNotNull('Design_Changes_Request');
                    break;
            }
        }

        $requests = $query->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequest = $requests->count();
        $correct = $requests->where('Correctness_Request', 1)->count();
        $incorrect = $totalRequest - $correct;

        $members = Member::orderBy('Name_Member')->get();

        return view('mcs.requests.index', compact(
            'requests',
            'totalRequest',
            'correct',
            'incorrect',
            'formattedDate',
            'date',
            'dateForInput',
            'members'
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

        $statusFilter = $request->input('statusFilter');
        if ($statusFilter) {
            switch ($statusFilter) {
                case 'ready':
                    $query->whereNotNull('Ready_Request');
                    break;
                case 'shipping':
                    $query->whereNotNull('Shipping_Request');
                    break;
                case 'production':
                    $query->whereNotNull('Production_Area_Request');
                    break;
                case 'design_change':
                    $query->whereNotNull('Design_Changes_Request');
                    break;
            }
        }

        $requests = $query->get();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No',
            'Time Request',
            'Area',
            'Rack',
            'Sum Request',
            'Urgenity',
            'Item',
            'Name',
            "1=Ready,2=Ship,\n3=Prod,4=Design", // ← \n = line break
            'Sum Stock',       // ← kolom J (setelah status)
            'Ready Stock',     // ← kolom K
            'Time Record',
            'Sum Record',
            'Member Request',
            'Member Record',
            'Updated',
            'Id'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:Q1')->getAlignment()->setWrapText(true);

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
                    array_fill(0, 17, '-'), // 17 kolom sesuai header
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
                $request->Sum_Stock ?? '',   // ← kolom J (Sum Stock)
                $readyStockDisplay,          // ← kolom K (Ready Stock)
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
            $columnsToCenter = ['E', 'F', 'I', 'J', 'M'];
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
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, false, true);

        $headerRow = $rows[1] ?? [];
        unset($rows[1]);

        // Deteksi kolom Sum Stock dan Status dari header
        $colStock = null;
        $colStatus = null;

        foreach ($headerRow as $colLetter => $headerName) {
            if ($headerName === null) continue;
            $cleaned = strtolower(trim(strval($headerName)));
            if (str_contains($cleaned, 'sum stock')) $colStock = $colLetter;
            if (str_contains($cleaned, '1=ready')) $colStatus = $colLetter;
        }

        $colStock = $colStock ?? 'J';
        $colStatus = $colStatus ?? 'I';

        $savedCount = 0;

        foreach ($rows as $row) {
            // Kolom terakhir = ID Request
            $colId = array_key_last($row);
            $rawId = $row[$colId] ?? null;

            if ($rawId === null || $rawId === '' || $rawId === '-') continue;

            $idValue = intval($rawId);
            if ($idValue <= 0) continue;

            // Ambil nilai Ready Stock (status) dan Sum Stock
            $rawStatus = strval($row[$colStatus] ?? '');
            $readyStatus = trim($rawStatus);
            $rawStock = $row[$colStock] ?? null;
            $hasStatus = in_array($readyStatus, ['1', '2', '3', '4']);
            $hasStock = ($rawStock !== null && $rawStock !== '' && is_numeric($rawStock));

            // VALIDASI: kedua kolom WAJIB terisi, jika salah satu kosong → skip
            if (!$hasStatus || !$hasStock) continue;

            $requestModel = RequestModel::find($idValue);
            if (!$requestModel) continue;

            $changed = false;

            // Update Sum Stock jika terisi
            if ($hasStock) {
                $requestModel->Sum_Stock = intval($rawStock);
                $changed = true;
            }

            // Update Status jika terisi DAN belum ada status sebelumnya
            if ($hasStatus) {
                $anyStatusFilled =
                    $requestModel->Ready_Request !== null ||
                    $requestModel->Shipping_Request !== null ||
                    $requestModel->Production_Area_Request !== null ||
                    $requestModel->Design_Changes_Request !== null;

                if (!$anyStatusFilled) {
                    $now = Carbon::now();
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
                    }
                    $changed = true;
                }
            }

            if ($changed) {
                $requestModel->save();
                $savedCount++;
            }
        }

        if ($savedCount === 0) {
            return redirect()->back()->with('error', 'Tidak ada data yang tersimpan. Pastikan kolom Ready Stock dan Sum Stock terisi.');
        }

        return redirect()->back()->with('success', "Berhasil menyimpan data.");
    }
}
