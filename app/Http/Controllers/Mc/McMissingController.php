<?php

namespace App\Http\Controllers\Mc;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;           // untuk HTTP Request
use App\Models\User;
use Carbon\Carbon; // alias model Request supaya gak bentrok
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class McMissingController extends Controller
{
    public function index()
    {
        $date = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();
        // Hitung waktu 2 hari kerja lalu (menggunakan SpecialDate)
        $workdaysAgo = \App\Models\SpecialDate::subWorkdays($now, 2);

        $allRequests = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->whereNotNull('Ready_Request')
            ->get();

        $requests = $allRequests->filter(function ($request) use ($workdaysAgo) {
            $latestStatusTime = null;
            if ($request->Design_Changes_Request) {
                $latestStatusTime = $request->Design_Changes_Request;
            } elseif ($request->Production_Area_Request) {
                $latestStatusTime = $request->Production_Area_Request;
            } elseif ($request->Shipping_Request) {
                $latestStatusTime = $request->Shipping_Request;
            } elseif ($request->Ready_Request) {
                $latestStatusTime = $request->Ready_Request;
            }

            if (! $latestStatusTime) {
                return false;
            }

            $request->latest_status_time = Carbon::parse($latestStatusTime);

            return $request->latest_status_time->lt($workdaysAgo);
        })->sortByDesc('latest_status_time')->values();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        return view('mcs.missings.index', compact('requests', 'totalRequests', 'formattedDate', 'date'));
    }

    public function export(Request $request)
    {
        $date = $request->input('Day_Request_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $now = Carbon::now();
        // Hitung waktu 2 hari kerja lalu (menggunakan SpecialDate)
        $workdaysAgo = \App\Models\SpecialDate::subWorkdays($now, 2);

        $allRequests = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->whereNotNull('Ready_Request')
            ->get();

        $requests = $allRequests->filter(function ($request) use ($workdaysAgo) {
            $latestStatusTime = null;
            if ($request->Design_Changes_Request) {
                $latestStatusTime = $request->Design_Changes_Request;
            } elseif ($request->Production_Area_Request) {
                $latestStatusTime = $request->Production_Area_Request;
            } elseif ($request->Shipping_Request) {
                $latestStatusTime = $request->Shipping_Request;
            } elseif ($request->Ready_Request) {
                $latestStatusTime = $request->Ready_Request;
            }

            if (! $latestStatusTime) {
                return false;
            }

            $request->latest_status_time = Carbon::parse($latestStatusTime);

            return $request->latest_status_time->lt($workdaysAgo);
        })->sortByDesc('latest_status_time')->values();

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['No', 'Rack', 'Item', 'Name', 'Sum', 'Time Request', 'Ready Stock', 'Day(s)', 'Hour(s) Minute(s)', 'PIC'];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        $sheet->setAutoFilter(
            $sheet->calculateWorksheetDimension() // otomatis dari A1 sampai kolom terakhir
        );

        // Isi data
        $row = 2;
        foreach ($requests as $index => $request) {
            // === 1. Time Request ===
            $timeRequest = ($request->Day_Request ?? '').' '.($request->Time_Request ?? '');

            // === 2. Ready Stock (sama seperti di view) ===
            $statuses = [];
            if ($request->Ready_Request) {
                $statuses[] = 'Ready: '.$request->Ready_Request;
            }
            if ($request->Shipping_Request) {
                $statuses[] = 'Shipping: '.$request->Shipping_Request;
            }
            if ($request->Production_Area_Request) {
                $statuses[] = 'Production: '.$request->Production_Area_Request;
            }
            if ($request->Design_Changes_Request) {
                $statuses[] = 'Design Change: '.$request->Design_Changes_Request;
            }
            $readyStockDisplay = implode(' | ', $statuses);

            // === 3. Overdue (sama seperti di view) ===
            $statusTimestamp = null;
            if ($request->Design_Changes_Request) {
                $statusTimestamp = $request->Design_Changes_Request;
            } elseif ($request->Production_Area_Request) {
                $statusTimestamp = $request->Production_Area_Request;
            } elseif ($request->Shipping_Request) {
                $statusTimestamp = $request->Shipping_Request;
            } elseif ($request->Ready_Request) {
                $statusTimestamp = $request->Ready_Request;
            }

            if ($statusTimestamp) {
                $statusTime = \Carbon\Carbon::parse($statusTimestamp);
                $now = \Carbon\Carbon::now();
                $totalSeconds = $now->timestamp - $statusTime->timestamp;

                if ($totalSeconds <= 0) {
                    $overdueDay = 0;
                    $overdueHM = 'On time';
                } else {
                    $days = floor($totalSeconds / 86400);
                    $hours = floor(($totalSeconds % 86400) / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);

                    $overdueDay = $days.' day(s)';

                    $hmParts = [];
                    if ($hours > 0) {
                        $hmParts[] = $hours.' hour(s)';
                    }
                    if ($minutes > 0) {
                        $hmParts[] = $minutes.' minute(s)';
                    }

                    $overdueHM = implode(' ', $hmParts);
                    if (empty($overdueHM)) {
                        $overdueHM = '0 minute(s)';
                    }
                }
            } else {
                $overdueDay = '-';
                $overdueHM = '-';
            }

            // === 4. Tulis ke Excel ===
            $sheet->fromArray([
                $index + 1,
                $request->Code_Rack,
                $request->Code_Item_Rack,
                $request->rack->Name_Item_Rack ?? '',
                $request->Sum_Request,
                $timeRequest,
                $readyStockDisplay,      // ← Ready Stock
                $overdueDay,             // ← Day(s)
                $overdueHM,              // ← Hour(s) Minute(s)
                $request->member->Name_Member ?? '-',
            ], null, 'A'.$row);

            $row++;
        }

        // 🔑 Auto size kolom
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan ke file di storage/app/public
        $fileName = 'Missing_List_DST_'.$date.'.xlsx';
        $filePath = storage_path('app/public/'.$fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function missing_mc()
    {
        $date = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // Ambil semua request yang belum ada status sama sekali
        $requests = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->whereNull('Ready_Request')
            ->whereNull('Shipping_Request')
            ->whereNull('Production_Area_Request')
            ->whereNull('Design_Changes_Request')
            ->get();

        $missingRequests = $requests->filter(function ($request) use ($now) {
            $requestTime = Carbon::parse($request->Day_Request.' '.$request->Time_Request);

            // Jika request di masa depan, skip
            if ($requestTime->gt($now)) {
                return false;
            }

            $current = $requestTime->copy();
            $workingHours = 0;

            // Loop per jam sampai mencapai now
            while ($current->lt($now)) {
                // Lewati non-hari kerja (weekend/libur)
                if (\App\Models\SpecialDate::isWorkday($current)) {
                    $workingHours++;
                }
                $current->addHour();
            }

            // Jika sudah lewat 24 jam kerja → missing
            return $workingHours > 24;
        })->values();

        $formattedDate = $now->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $missingRequests->count();

        return view('mcs.missings.mc', compact('missingRequests', 'totalRequests', 'formattedDate', 'date'));
    }

    public function missing_mc_export(Request $request)
    {
        $date = $request->input('Day_Request_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $now = Carbon::now();
        // Ambil semua request yang belum ada status sama sekali
        $requests = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->whereNull('Ready_Request')
            ->whereNull('Shipping_Request')
            ->whereNull('Production_Area_Request')
            ->whereNull('Design_Changes_Request')
            ->get();

        $missingRequests = $requests->filter(function ($request) use ($now) {
            $requestTime = Carbon::parse($request->Day_Request.' '.$request->Time_Request);

            // Jika request di masa depan, skip
            if ($requestTime->gt($now)) {
                return false;
            }

            $current = $requestTime->copy();
            $workingHours = 0;

            // Loop per jam sampai mencapai now
            while ($current->lt($now)) {
                // Lewati non-hari kerja (weekend/libur)
                if (\App\Models\SpecialDate::isWorkday($current)) {
                    $workingHours++;
                }
                $current->addHour();
            }

            // Jika sudah lewat 24 jam kerja → missing
            return $workingHours > 24;
        })->values();

        // Buat Spreadsheet
        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No', 'Time Request', 'Area', 'Rack', 'Sum Request', 'Urgenity', 'Item', 'Name',
            "1=Ready,2=Ship,\n3=Prod,4=Design", // ← \n = line break
            'Sum Stock', 'Ready Stock', 'Estimation Date', 'Time Record', 'Sum Record', 'Member Request',
            'Member Record', 'Updated', 'Id',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
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

        foreach ($missingRequests as $index => $request) {
            // Reset nomor & kasih spasi kalau ganti user
            if ($lastUser !== null && $lastUser != $request->Id_User) {
                $sheet->fromArray(
                    array_fill(0, 18, '-'), // 18 kolom sesuai header
                    null,
                    'A'.$row
                );
                $row++;
                $no = 1; // reset nomor
            }

            // Di dalam foreach ($requests as $request)
            $readyDisplay = [];

            if ($request->Ready_Request) {
                $readyDisplay[] = 'Ready: '.$request->Ready_Request;
            }
            if ($request->Shipping_Request) {
                $readyDisplay[] = 'Shipping: '.$request->Shipping_Request;
            }
            if ($request->Production_Area_Request) {
                $readyDisplay[] = 'Production: '.$request->Production_Area_Request;
            }
            if ($request->Design_Changes_Request) {
                $readyDisplay[] = 'Design: '.$request->Design_Changes_Request;
            }

            $readyStockDisplay = implode(' | ', $readyDisplay);

            $timeRequest = ($request->Day_Request ?? '').' '.($request->Time_Request ?? '');
            $timeRecord = ($request->record->Day_Record ?? '').' '.($request->record->Time_Record ?? '');

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

            $estimationDisplay = '';
            if ($request->Estimation_Stock) {
                $estimationDisplay = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    \Carbon\Carbon::parse($request->Estimation_Stock)
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
            ], null, 'A'.$row);

            $lastUser = $request->Id_User;
            $no++;
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $columnsToCenter = ['E', 'F', 'I', 'J', 'L', 'N'];
            foreach ($columnsToCenter as $col) {
                $range = $col.'2:'.$col.$lastRow;
                $sheet->getStyle($range)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
        }

        $sheet->getStyle('L2:L1000')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
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
        $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_GREATERTHANOREQUAL);
        $validation->setFormula1(\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(\Carbon\Carbon::parse('1900-01-01')));
        $sheet->setDataValidation('L2:L1000', $validation);

        // 🔑 Auto size kolom
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan ke file
        $fileName = 'Missing_List_MC_'.$date.'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/'.$fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function missing_estimation()
    {
        $date = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // Request yang punya status Shipping atau Design Change
        // DAN Estimation_Stock sudah lewat >= 48 jam
        // DAN Ok_Stock belum 1
        $requests = RequestModel::with('member', 'record', 'rack')
            ->where('Status_Request', '!=', 'Done')
            ->where(function ($query) {
                $query->whereNotNull('Shipping_Request')
                    ->orWhereNotNull('Design_Changes_Request');
            })
            ->whereNotNull('Estimation_Stock')
            ->where('Estimation_Stock', '<', $now->copy()->subHours(48))
            ->where(function ($query) {
                $query->whereNull('Ok_Stock')
                    ->orWhere('Ok_Stock', '!=', 1);
            })
            ->orderBy('Estimation_Stock', 'desc')
            ->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        return view('mcs.missings.estimation', compact('requests', 'totalRequests', 'formattedDate', 'date'));
    }

    public function missing_estimation_export(Request $request)
    {
        $date = $request->input('Day_Request_Hidden', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($date)->format('Y-m-d');
        $now = Carbon::now();

        $requests = RequestModel::with('member', 'record', 'rack')
            ->where('Status_Request', '!=', 'Done')
            ->where(function ($query) {
                $query->whereNotNull('Shipping_Request')
                    ->orWhereNotNull('Design_Changes_Request');
            })
            ->whereNotNull('Estimation_Stock')
            ->where('Estimation_Stock', '<', $now->copy()->subHours(48))
            ->where(function ($query) {
                $query->whereNull('Ok_Stock')
                    ->orWhere('Ok_Stock', '!=', 1);
            })
            ->orderBy('Estimation_Stock', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'No', 'Rack', 'Item', 'Name', 'Sum', 'Status',
            'Time Request', 'Estimation Date', 'Overdue Day(s)',
            'Overdue Hour(s) Minute(s)', 'PIC',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $row = 2;
        foreach ($requests as $index => $req) {
            // Determine status type
            $statusLabel = '';
            if ($req->Design_Changes_Request) {
                $statusLabel = 'Design Change';
            } elseif ($req->Shipping_Request) {
                $statusLabel = 'Shipping';
            }

            $timeRequest = ($req->Day_Request ?? '').' '.($req->Time_Request ?? '');
            $estimationDate = '';
            if ($req->Estimation_Stock) {
                $estimationDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    \Carbon\Carbon::parse($req->Estimation_Stock)
                );
            }

            // Calculate overdue from Estimation_Stock
            $overdueDay = '-';
            $overdueHM = '-';
            if ($req->Estimation_Stock) {
                $estTime = Carbon::parse($req->Estimation_Stock);
                $totalSeconds = $now->timestamp - $estTime->timestamp;

                if ($totalSeconds > 0) {
                    $days = floor($totalSeconds / 86400);
                    $hours = floor(($totalSeconds % 86400) / 3600);
                    $minutes = floor(($totalSeconds % 3600) / 60);

                    $overdueDay = $days.' day(s)';
                    $hmParts = [];
                    if ($hours > 0) {
                        $hmParts[] = $hours.' hour(s)';
                    }
                    if ($minutes > 0) {
                        $hmParts[] = $minutes.' minute(s)';
                    }
                    $overdueHM = implode(' ', $hmParts) ?: '0 minute(s)';
                }
            }

            $sheet->fromArray([
                $index + 1,
                $req->Code_Rack,
                $req->Code_Item_Rack,
                $req->rack->Name_Item_Rack ?? '',
                $req->Sum_Request,
                $statusLabel,
                $timeRequest,
                $estimationDate,
                $overdueDay,
                $overdueHM,
                $req->member->Name_Member ?? '-',
            ], null, 'A'.$row);

            $row++;
        }

        $sheet->getStyle('H2:H1000')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $validation = $sheet->getCell('H2')->getDataValidation();
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
        $validation->setFormula1(\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(\Carbon\Carbon::parse('1900-01-01')));
        $sheet->setDataValidation('H2:H1000', $validation);

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Missing_Estimation_'.$date.'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/'.$fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function oke_estimation()
    {
        $date = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // Request yang sudah di-OK (Ok_Stock == 1) dan bertipe Shipping/Design Change
        $requests = RequestModel::with('member', 'record', 'rack')
            ->where('Status_Request', '!=', 'Done')
            ->where(function ($query) {
                $query->whereNotNull('Shipping_Request')
                    ->orWhereNotNull('Design_Changes_Request');
            })
            ->whereNotNull('Estimation_Stock')
            ->where('Ok_Stock', 1)
            ->orderBy('Time_Ok_Stock', 'desc')
            ->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        return view('mcs.missings.oke_estimation', compact('requests', 'totalRequests', 'formattedDate', 'date'));
    }

    public function oke_estimation_export(Request $request)
    {
        $date = $request->input('Day_Request_Hidden', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($date)->format('Y-m-d');
        $now = Carbon::now();

        $requests = RequestModel::with('member', 'record', 'rack')
            ->where('Status_Request', '!=', 'Done')
            ->where(function ($query) {
                $query->whereNotNull('Shipping_Request')
                    ->orWhereNotNull('Design_Changes_Request');
            })
            ->whereNotNull('Estimation_Stock')
            ->where('Ok_Stock', 1)
            ->orderBy('Time_Ok_Stock', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'No', 'Rack', 'Item', 'Name', 'Sum', 'Status OK',
            'Time Request', 'Estimation Date', 'Stock Shipping',
            'Time OK', 'PIC',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $row = 2;
        foreach ($requests as $index => $req) {
            $statusLabel = '';
            if ($req->Design_Changes_Request) {
                $statusLabel = 'Oke Perubahan Design';
            } elseif ($req->Shipping_Request) {
                $statusLabel = 'Oke Shipping';
            }

            $timeRequest = ($req->Day_Request ?? '').' '.($req->Time_Request ?? '');
            $estimationDate = '';
            if ($req->Estimation_Stock) {
                $estimationDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    \Carbon\Carbon::parse($req->Estimation_Stock)
                );
            }

            $timeOk = $req->Time_Ok_Stock ? Carbon::parse($req->Time_Ok_Stock)->format('Y-m-d H:i:s') : '';

            $sheet->fromArray([
                $index + 1,
                $req->Code_Rack,
                $req->Code_Item_Rack,
                $req->rack->Name_Item_Rack ?? '',
                $req->Sum_Request,
                $statusLabel,
                $timeRequest,
                $estimationDate,
                $req->Stock_Shipping ?? '',
                $timeOk,
                $req->member->Name_Member ?? '-',
            ], null, 'A'.$row);

            $row++;
        }

        $sheet->getStyle('H2:H1000')->getNumberFormat()->setFormatCode('DD/MM/YYYY');

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Oke_Estimation_'.$date.'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/'.$fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
