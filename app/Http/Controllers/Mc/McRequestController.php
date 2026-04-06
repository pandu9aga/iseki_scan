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
            "1=Ready,2=Ship,\n3=Prod,4=Design",
            'Sum Stock',
            'Ready Stock',
            'Estimation Date',
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
                $request->Sum_Stock ?? '',
                $readyStockDisplay,
                $estimationDisplay,
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
            $columnsToCenter = ['E', 'F', 'I', 'J', 'L', 'N'];
            foreach ($columnsToCenter as $col) {
                $range = $col . '2:' . $col . $lastRow;
                $sheet->getStyle($range)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
        }

        // Apply Date Format & Data Validation to Estimation Date column (L)
        // Up to row 1000 so empty rows also have the date picker
        $sheet->getStyle('L2:L1000')->getNumberFormat()
            ->setFormatCode('DD/MM/YYYY');

        $validation = $sheet->getCell('L2')->getDataValidation();
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

        $sheet->setDataValidation('L2:L1000', $validation);

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
        $colEstimation = $colEstimation ?? 'L';

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

            // VALIDASI: Status dan Sum Stock WAJIB terisi
            if (!$hasStatus || !$hasStock) continue;

            // VALIDASI: Jika status 3 (Shipping) atau 4 (Design Change), Estimation Date WAJIB
            $parsedEstimation = null;
            if (in_array($readyStatus, ['3', '4'])) {
                if ($rawEstimation === null || trim(strval($rawEstimation)) === '') {
                    $skippedEstimation++;
                    continue; // Skip: status 3/4 tapi tidak ada estimation date
                }
                // Parse estimation date (support format d/m/Y, d-m-Y, Y-m-d, Excel serial)
                try {
                    $estStr = trim(strval($rawEstimation));
                    if (is_numeric($estStr)) {
                        // Excel serial date number
                        $parsedEstimation = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(intval($estStr)));
                    } else {
                        $parsedEstimation = Carbon::parse($estStr);
                    }
                } catch (\Exception $e) {
                    $skippedEstimation++;
                    continue; // Skip: format tanggal tidak valid
                }
            } elseif ($rawEstimation !== null && trim(strval($rawEstimation)) !== '') {
                // Status 1/2 tapi ada estimation → simpan juga
                try {
                    $estStr = trim(strval($rawEstimation));
                    if (is_numeric($estStr)) {
                        $parsedEstimation = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(intval($estStr)));
                    } else {
                        $parsedEstimation = Carbon::parse($estStr);
                    }
                } catch (\Exception $e) {
                    // Abaikan error parsing untuk status 1/2
                }
            }

            $requestModel = RequestModel::find($idValue);
            if (!$requestModel) continue;

            $changed = false;

            // Update Sum Stock
            $requestModel->Sum_Stock = intval($rawStock);
            $changed = true;

            // Update Estimation Date jika ada
            if ($parsedEstimation) {
                $requestModel->Estimation_Stock = $parsedEstimation;
                $changed = true;
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

    public function okStock($id)
    {
        $requestModel = RequestModel::findOrFail($id);
        $requestModel->Ok_Stock = 1;
        $requestModel->Time_Ok_Stock = Carbon::now();
        $requestModel->save();

        return redirect()->back()->with('success', 'OK Stock berhasil diupdate.');
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
                ->addColumn('Sum_Record', function ($r) {
                    return optional($r->record)->Sum_Record ?? '';
                })
                ->addColumn('Member_Request', function ($r) {
                    return optional($r->member)->Name_Member ?? '';
                })
                ->addColumn('Member_Record', function ($r) {
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
                ->rawColumns(['Urgent_Request', 'ready_status_display', 'Status_Request_Display', 'Type_Tractor_Rack'])
                ->make(true);
        }

        // Non-AJAX: kirim daftar member ke view
        $members = Member::where('Status_Non_Active', '!=', 1)->orWhereNull('Status_Non_Active')->orderBy('Name_Member')->get(['Id_Member', 'Name_Member']);
        return view('mcs.requests.search', compact('members'));
    }
}
