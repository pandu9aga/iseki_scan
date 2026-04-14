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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

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

        $statusFilters = request('statusFilter', []);
        if (!empty($statusFilters)) {
            $query->where(function ($q) use ($statusFilters) {
                foreach ($statusFilters as $sf) {
                    switch ($sf) {
                        case '1':
                            $q->orWhereNotNull('Ready_Request');
                            break;
                        case '2':
                            $q->orWhereNotNull('Shipping_Request');
                            break;
                        case '3':
                            $q->orWhereNotNull('Production_Area_Request');
                            break;
                        case '4':
                            $q->orWhereNotNull('Design_Changes_Request');
                            break;
                    }
                }
            });
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

        $statusFilters = $request->input('statusFilter', []);
        if (!empty($statusFilters)) {
            $query->where(function ($q) use ($statusFilters) {
                foreach ($statusFilters as $sf) {
                    switch ($sf) {
                        case '1':
                            $q->orWhereNotNull('Ready_Request');
                            break;
                        case '2':
                            $q->orWhereNotNull('Shipping_Request');
                            break;
                        case '3':
                            $q->orWhereNotNull('Production_Area_Request');
                            break;
                        case '4':
                            $q->orWhereNotNull('Design_Changes_Request');
                            break;
                    }
                }
            });
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

        $statusFilters = $request->input('statusFilter', []);
        if (!empty($statusFilters)) {
            $query->where(function ($q) use ($statusFilters) {
                foreach ($statusFilters as $sf) {
                    switch ($sf) {
                        case '1':
                            $q->orWhereNotNull('Ready_Request');
                            break;
                        case '2':
                            $q->orWhereNotNull('Shipping_Request');
                            break;
                        case '3':
                            $q->orWhereNotNull('Production_Area_Request');
                            break;
                        case '4':
                            $q->orWhereNotNull('Design_Changes_Request');
                            break;
                    }
                }
            });
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
            "1=Ready,2=Ship,\n3=Prod,4=Design",
            'Sum Stock',
            'Estimation Date',
            'Ready Stock',
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
        $sheet->getStyle('A1:R1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:R1')->getAlignment()->setWrapText(true);

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
                    array_fill(0, 18, '-'), // 18 kolom sesuai header
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

            // Determine Sum Stock display value
            $sumStockDisplay = '';
            if ($statusCode == '2' || $statusCode == '4') {
                $sumStockDisplay = ''; // kosongan aja
            } else {
                $sumStockDisplay = $request->Sum_Stock ?? '';
            }

            // Format Estimation Date to Excel serial date for date picker
            $estimationDisplay = '';
            if ($request->Estimation_Stock) {
                $estimationDisplay = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    Carbon::parse($request->Estimation_Stock)
                );
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
                $sumStockDisplay,
                $estimationDisplay,
                $readyStockDisplay,
                $timeRecord,
                optional($request->record)->Sum_Record ?? '',
                $request->Is_User == 1 ? (optional($request->user)->Name_User ?? 'Admin') : ($request->member->Name_Member ?? ''),
                optional($request->record)->Is_User == 1 ? (optional($request->record->user)->Name_User ?? 'Admin') : (optional($request->record)->member->Name_Member ?? ''),
                $request->Updated_At_Request,
                $request->Id_Request,
            ], null, 'A' . $row);

            // Set background color for readonly Sum Stock based on status
            if ($statusCode == '2') {
                $sheet->getStyle('J' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ADD8E6'] // Light Blue
                    ],
                    'font' => [
                        'color' => ['rgb' => '000000']
                    ]
                ]);
            } elseif ($statusCode == '4') {
                $sheet->getStyle('J' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFFE0'] // Light Yellow
                    ],
                    'font' => [
                        'color' => ['rgb' => '000000']
                    ]
                ]);
            }

            $lastUser = $request->Id_User;
            $no++;
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $columnsToCenter = ['E', 'F', 'I', 'J', 'K', 'N'];
            foreach ($columnsToCenter as $col) {
                $range = $col . '2:' . $col . $lastRow;
                $sheet->getStyle($range)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
        }

        // Apply Date Format & Data Validation to Estimation Date column (K)
        // Up to row 1000 so empty rows also have the date picker
        $sheet->getStyle('K2:K1000')->getNumberFormat()
            ->setFormatCode('DD/MM/YYYY');

        $validation = $sheet->getCell('K2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Input Error');
        $validation->setError('Harus berupa tanggal!');
        $validation->setPromptTitle('Pilih Tanggal');
        $validation->setPrompt('Format: DD/MM/YYYY');
        // Setting data validation for exactly dates >= 1 Jan 1900
        $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_GREATERTHANOREQUAL);
        $validation->setFormula1(\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse('1900-01-01')));

        $sheet->setDataValidation('K2:K1000', $validation);

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

        // Deteksi kolom dari header
        $colStock = null;
        $colStatus = null;
        $colEstimation = null;

        foreach ($headerRow as $colLetter => $headerName) {
            if ($headerName === null) continue;
            $cleaned = strtolower(trim(strval($headerName)));
            if (str_contains($cleaned, 'sum stock')) $colStock = $colLetter;
            if (str_contains($cleaned, '1=ready')) $colStatus = $colLetter;
            if (str_contains($cleaned, 'estimation')) $colEstimation = $colLetter;
        }

        $colStock = $colStock ?? 'J';
        $colStatus = $colStatus ?? 'I';
        $colEstimation = $colEstimation ?? 'K';

        $savedCount = 0;
        $skippedEstimation = 0;

        foreach ($rows as $row) {
            // Kolom terakhir = ID Request
            $colId = array_key_last($row);
            $rawId = $row[$colId] ?? null;

            if ($rawId === null || $rawId === '' || $rawId === '-') continue;

            $idValue = intval($rawId);
            if ($idValue <= 0) continue;

            // Ambil nilai Status, Sum Stock, dan Estimation Date
            $rawStatus = strval($row[$colStatus] ?? '');
            $readyStatus = trim($rawStatus);
            $rawStock = $row[$colStock] ?? null;
            $rawEstimation = $row[$colEstimation] ?? null;

            $hasStatus = in_array($readyStatus, ['1', '2', '3', '4']);
            $hasStock = ($rawStock !== null && $rawStock !== '' && is_numeric($rawStock));

            // VALIDASI per status:
            // Status 1 (Ready): WAJIB Sum Stock
            // Status 2/4 (Shipping/Design Change): WAJIB Estimation Date, Sum Stock opsional
            // Status 3 (Production): WAJIB Sum Stock
            if (!$hasStatus) continue;

            if (in_array($readyStatus, ['1', '3']) && !$hasStock) continue;

            // Parse Estimation Date
            $parsedEstimation = null;
            if (in_array($readyStatus, ['2', '4'])) {
                if ($rawEstimation === null || trim(strval($rawEstimation)) === '') {
                    $skippedEstimation++;
                    continue; // Skip: status 2/4 tapi tidak ada estimation date
                }
                try {
                    $estStr = trim(strval($rawEstimation));
                    if (is_numeric($estStr)) {
                        $parsedEstimation = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(intval($estStr)));
                    } else {
                        $parsedEstimation = Carbon::parse($estStr);
                    }
                } catch (\Exception $e) {
                    $skippedEstimation++;
                    continue;
                }
            } elseif ($rawEstimation !== null && trim(strval($rawEstimation)) !== '') {
                try {
                    $estStr = trim(strval($rawEstimation));
                    if (is_numeric($estStr)) {
                        $parsedEstimation = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(intval($estStr)));
                    } else {
                        $parsedEstimation = Carbon::parse($estStr);
                    }
                } catch (\Exception $e) {
                    // Abaikan error parsing untuk status 1/3
                }
            }

            $requestModel = RequestModel::find($idValue);
            if (!$requestModel) continue;

            $changed = false;

            // Update Sum Stock (jika ada) hanya untuk status 1 dan 3
            if ($hasStock && in_array($readyStatus, ['1', '3'])) {
                $requestModel->Sum_Stock = intval($rawStock);
                $changed = true;
            }

            // Update Estimation Date jika ada
            if ($parsedEstimation) {
                // Konversi keduanya ke format Y-m-d untuk perbandingan agar akurat
                $oldEst = $requestModel->Estimation_Stock ? Carbon::parse($requestModel->Estimation_Stock)->format('Y-m-d') : null;
                $newEst = $parsedEstimation->format('Y-m-d');

                if ($oldEst !== $newEst) {
                    $requestModel->Estimation_Stock = $parsedEstimation;
                    // Reset Ok_Stock jika tanggal estimation berubah agar butuh validasi ulang
                    $requestModel->Ok_Stock = null;
                    $requestModel->Time_Ok_Stock = null;
                    $changed = true;
                }
            }

            // Update Status jika terisi DAN belum ada status sebelumnya
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

            if ($changed) {
                $requestModel->save();
                $savedCount++;
            }
        }

        if ($savedCount === 0) {
            $msg = 'Tidak ada data yang tersimpan. Pastikan kolom Status dan Sum Stock terisi.';
            if ($skippedEstimation > 0) {
                $msg .= " ({$skippedEstimation} baris dilewati karena Estimation Date kosong untuk status Shipping/Design Change.)";
            }
            return redirect()->back()->with('error', $msg);
        }

        $successMsg = "Berhasil menyimpan {$savedCount} baris data.";
        if ($skippedEstimation > 0) {
            $successMsg .= " ({$skippedEstimation} baris dilewati karena Estimation Date kosong.)";
        }
        return redirect()->back()->with('success', $successMsg);
    }

    public function okStock(Request $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        // Validasi: untuk tipe 2/4 harus isi Stock_Shipping dulu
        $isShippingOrDesign = ($requestModel->Shipping_Request !== null || $requestModel->Design_Changes_Request !== null);
        if ($isShippingOrDesign && empty($requestModel->Stock_Shipping)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Harus isi Stock Shipping terlebih dahulu!']);
            }
            return redirect()->back()->with('error', 'Harus isi Stock Shipping terlebih dahulu!');
        }

        if ($request->ajax()) {
            $status = $request->input('status');
            $requestModel->Ok_Stock = $status == 1 ? 1 : null;
            $requestModel->Time_Ok_Stock = Carbon::now();
            $requestModel->save();

            return response()->json(['success' => true, 'message' => 'Status OK Stock berhasil diupdate.']);
        }

        $requestModel->Ok_Stock = 1;
        $requestModel->Time_Ok_Stock = Carbon::now();
        $requestModel->save();

        return redirect()->back()->with('success', 'OK Stock berhasil diupdate.');
    }

    public function noStock($id)
    {
        $requestModel = RequestModel::findOrFail($id);
        $requestModel->Ok_Stock = 2; // 2 indicates NO Stock
        $requestModel->Time_Ok_Stock = Carbon::now();
        $requestModel->save();

        return redirect()->back()->with('success', 'NO Stock berhasil diupdate.');
    }

    public function updateStockShipping(Request $request, $id)
    {
        $requestModel = RequestModel::findOrFail($id);
        $value = $request->input('stock_shipping');

        if ($value === null || $value === '') {
            $requestModel->Stock_Shipping = null;
        } else {
            $requestModel->Stock_Shipping = intval($value);
        }
        $requestModel->save();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Stock Shipping berhasil diupdate.']);
        }
        return redirect()->back()->with('success', 'Stock Shipping berhasil diupdate.');
    }

    public function exportSearch(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status'     => 'required',
        ]);

        $startDate  = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $endDate    = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        $statusFilter = $request->input('status');

        $query = RequestModel::with('member', 'record', 'rack')
            ->whereDate('Day_Request', '>=', $startDate)
            ->whereDate('Day_Request', '<=', $endDate)
            ->orderBy('Id_User')
            ->orderBy('Urgent_Request', 'desc')
            ->orderBy('Area_Request')
            ->orderBy('Time_Request');

        switch ($statusFilter) {
            case 'no_status':
                $query->whereNull('Ready_Request')
                    ->whereNull('Shipping_Request')
                    ->whereNull('Production_Area_Request')
                    ->whereNull('Design_Changes_Request');
                break;
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

        $requests = $query->get();

        // Buat Spreadsheet (sama persis dengan export di MC Request)
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'No',
            'Time Request',
            'Area',
            'Rack',
            'Sum Request',
            'Urgenity',
            'Item',
            'Name',
            "1=Ready,2=Ship,\n3=Prod,4=Design",
            'Sum Stock',
            'Estimation Date',
            'Ready Stock',
            'Time Record',
            'Sum Record',
            'Member Request',
            'Member Record',
            'Updated',
            'Id'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:R1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:R1')->getAlignment()->setWrapText(true);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $row = 2;
        $lastUser = null;
        $no = 1;

        foreach ($requests as $req) {
            if ($lastUser !== null && $lastUser != $req->Id_User) {
                $sheet->fromArray(array_fill(0, 18, '-'), null, 'A' . $row);
                $row++;
                $no = 1;
            }

            $readyDisplay = [];
            if ($req->Ready_Request) $readyDisplay[] = 'Ready: ' . $req->Ready_Request;
            if ($req->Shipping_Request) $readyDisplay[] = 'Shipping: ' . $req->Shipping_Request;
            if ($req->Production_Area_Request) $readyDisplay[] = 'Production: ' . $req->Production_Area_Request;
            if ($req->Design_Changes_Request) $readyDisplay[] = 'Design: ' . $req->Design_Changes_Request;
            $readyStockDisplay = implode(' | ', $readyDisplay);

            $timeRequest = ($req->Day_Request ?? '') . ' ' . ($req->Time_Request ?? '');
            $timeRecord  = ($req->record->Day_Record ?? '') . ' ' . ($req->record->Time_Record ?? '');

            $statusCode = '';
            if ($req->Ready_Request !== null) $statusCode = '1';
            elseif ($req->Shipping_Request !== null) $statusCode = '2';
            elseif ($req->Production_Area_Request !== null) $statusCode = '3';
            elseif ($req->Design_Changes_Request !== null) $statusCode = '4';

            // Determine Sum Stock display value
            $sumStockDisplay = '';
            if ($statusCode == '2' || $statusCode == '4') {
                $sumStockDisplay = ''; // kosongan aja
            } else {
                $sumStockDisplay = $req->Sum_Stock ?? '';
            }

            $estimationDisplay = '';
            if ($req->Estimation_Stock) {
                $estimationDisplay = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    Carbon::parse($req->Estimation_Stock)
                );
            }

            $sheet->fromArray([
                $no,
                $timeRequest,
                $req->Area_Request ?? '',
                $req->Code_Rack,
                $req->Sum_Request,
                $req->Urgent_Request == 1 ? '✓' : '',
                $req->Code_Item_Rack,
                $req->rack->Name_Item_Rack ?? '',
                $statusCode,
                $sumStockDisplay,
                $estimationDisplay,
                $readyStockDisplay,
                $timeRecord,
                optional($req->record)->Sum_Record ?? '',
                $req->Is_User == 1 ? (optional($req->user)->Name_User ?? 'Admin') : ($req->member->Name_Member ?? ''),
                optional($req->record)->Is_User == 1 ? (optional($req->record->user)->Name_User ?? 'Admin') : (optional($req->record)->member->Name_Member ?? ''),
                $req->Updated_At_Request,
                $req->Id_Request,
            ], null, 'A' . $row);

            // Set background color for readonly Sum Stock based on status
            if ($statusCode == '2') {
                $sheet->getStyle('J' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ADD8E6'] // Light Blue
                    ],
                    'font' => [
                        'color' => ['rgb' => '000000']
                    ]
                ]);
            } elseif ($statusCode == '4') {
                $sheet->getStyle('J' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFFE0'] // Light Yellow
                    ],
                    'font' => [
                        'color' => ['rgb' => '000000']
                    ]
                ]);
            }

            $lastUser = $req->Id_User;
            $no++;
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $columnsToCenter = ['E', 'F', 'I', 'J', 'K', 'N'];
            foreach ($columnsToCenter as $col) {
                $sheet->getStyle($col . '2:' . $col . $lastRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
        }

        $sheet->getStyle('K2:K1000')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $validation = $sheet->getCell('K2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Input Error');
        $validation->setError('Harus berupa tanggal!');
        $validation->setPromptTitle('Pilih Tanggal');
        $validation->setPrompt('Format: DD/MM/YYYY');
        $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_GREATERTHANOREQUAL);
        $validation->setFormula1(\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse('1900-01-01')));
        $sheet->setDataValidation('K2:K1000', $validation);

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Request_Search_{$startDate}_to_{$endDate}.xlsx";
        $writer   = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function search()
    {
        if (request()->ajax()) {
            $query = RequestModel::with('member', 'record', 'rack');

            if ($statusFilter = request('statusFilter')) {
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

            return DataTables::eloquent($query)
                ->editColumn('Day_Request', function ($r) {
                    return $r->Day_Request . ' ' . $r->Time_Request;
                })
                ->addColumn('Urgent_Request', function ($r) {
                    return $r->Urgent_Request == 1 ? '✓' : '';
                })
                ->addColumn('Name', function ($r) {
                    return optional($r->rack)->Name_Item_Rack ?? '';
                })
                ->addColumn('Type_Tractor_Rack', function ($r) {
                    $type = optional($r->rack)->Type_Tractor_Rack ?? '-';
                    if ($type === '-') {
                        return '-';
                    }
                    $short = \Illuminate\Support\Str::limit($type, 20);
                    return '<span title="' . e($type) . '">' . e($short) . '</span>';
                })
                ->addColumn('Time_Record', function ($r) {
                    $day = optional($r->record)->Day_Record ?? '';
                    $time = optional($r->record)->Time_Record ?? '';
                    return trim("$day $time");
                })
                ->addColumn('Status_Request_Display', function ($r) {
                    $status = $r->Status_Request ?? '';
                    switch ($status) {
                        case 'Waiting':
                            return '<span class="badge badge-warning">Waiting</span>';
                        case 'Done':
                            return '<span class="badge badge-success">Done</span>';
                        default:
                            return '<span class="badge badge-secondary">' . e($status) . '</span>';
                    }
                })
                ->editColumn('Sum_Stock', function ($r) {
                    if ($r->Shipping_Request || $r->Design_Changes_Request) {
                        return ''; // kosongan aja
                    }
                    return $r->Sum_Stock ?? '';
                })
                ->addColumn('Sum_Record', function ($r) {
                    return optional($r->record)->Sum_Record ?? '';
                })
                ->addColumn('Member_Request', function ($r) {
                    if ($r->Is_User == 1) {
                        return optional($r->user)->Name_User ?? 'Admin';
                    }
                    return optional($r->member)->Name_Member ?? '';
                })
                ->addColumn('Member_Record', function ($r) {
                    if (optional($r->record)->Is_User == 1) {
                        return optional($r->record->user)->Name_User ?? 'Admin';
                    }
                    return optional($r->record)?->member?->Name_Member ?? '';
                })
                ->editColumn('Updated_At_Request', function ($r) {
                    return $r->Updated_At_Request ?? '';
                })
                ->addColumn('ready_status_display', function ($r) {
                    $statuses = [];
                    if ($r->Ready_Request) {
                        $statuses[] = '<span class="badge badge-success">Ready</span>: ' . $r->Ready_Request;
                    }
                    if ($r->Shipping_Request) {
                        $statuses[] = '<span class="badge badge-info">Shipping</span>: ' . $r->Shipping_Request;
                    }
                    if ($r->Production_Area_Request) {
                        $statuses[] = '<span class="badge badge-primary">Production</span>: ' . $r->Production_Area_Request;
                    }
                    if ($r->Design_Changes_Request) {
                        $statuses[] = '<span class="badge badge-warning">Design Change</span>: ' . $r->Design_Changes_Request;
                    }
                    return implode(' | ', $statuses);
                })
                ->filterColumn('Id_User', function ($query, $keyword) {
                    if ($keyword !== '') {
                        $query->where('Id_User', $keyword); // ✅ exact match
                    }
                })
                ->orderColumn('Day_Request', function ($query, $order) {
                    $query->orderBy('Day_Request', $order)->orderBy('Time_Request', $order);
                })
                ->orderColumn('ready_status_display', function ($query, $order) {
                    $query->orderByRaw('GREATEST(COALESCE(Ready_Request, "1000-01-01"), COALESCE(Shipping_Request, "1000-01-01"), COALESCE(Production_Area_Request, "1000-01-01"), COALESCE(Design_Changes_Request, "1000-01-01")) ' . $order);
                })
                ->addColumn('Estimation_Date_Display', function ($r) {
                    if ($r->Estimation_Stock) {
                        return \Carbon\Carbon::parse($r->Estimation_Stock)->format('d/m/Y');
                    }
                    return '-';
                })
                ->addColumn('Estimation_Stock', function ($r) {
                    if ($r->Estimation_Stock) {
                        return \Carbon\Carbon::parse($r->Estimation_Stock)->format('d/m/Y');
                    }
                    return '';
                })
                ->addColumn('Ok_Stock', function ($r) {
                    if ($r->Shipping_Request || $r->Design_Changes_Request) {
                        $checked = $r->Ok_Stock == 1 ? 'checked' : '';
                        $disabled = empty($r->Stock_Shipping) ? 'disabled' : '';
                        $html = '<div class="custom-control custom-switch d-inline-block" title="Toggle OK Stock">
                                    <input type="checkbox" class="custom-control-input ok-stock-switch" 
                                           id="okSwitch_' . $r->Id_Request . '" 
                                           data-id="' . $r->Id_Request . '" 
                                           ' . $checked . ' ' . $disabled . '>
                                    <label class="custom-control-label" for="okSwitch_' . $r->Id_Request . '"></label>
                                </div>';
                        if (empty($r->Stock_Shipping)) {
                            $html .= '<small class="text-muted d-block" style="font-size:10px;">Isi Stock Shipping</small>';
                        }
                        return $html;
                    }
                    return '';
                })
                ->addColumn('Stock_Shipping', function ($r) {
                    if ($r->Shipping_Request || $r->Design_Changes_Request) {
                        return '<input type="number" class="form-control form-control-sm stock-shipping-input" 
                                       data-id="' . $r->Id_Request . '" 
                                       value="' . $r->Stock_Shipping . '" 
                                       placeholder="0" style="width:80px; display:inline-block;">';
                    }
                    return '';
                })
                ->rawColumns(['Urgent_Request', 'ready_status_display', 'Status_Request_Display', 'Type_Tractor_Rack', 'Ok_Stock', 'Stock_Shipping'])
                ->make(true);
        }

        // Non-AJAX: kirim daftar member ke view
        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get(['Id_Member', 'Name_Member']);
        return view('mcs.requests.search', compact('members'));
    }
}
