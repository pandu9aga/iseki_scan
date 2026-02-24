<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;           // untuk HTTP Request
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Request as RequestModel; // alias model Request supaya gak bentrok
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MissingController extends Controller
{
    public function index(){
        $date = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();
        // Hitung waktu 2 hari kerja lalu (tanpa Sabtu dan Minggu)
        $workdaysAgo = $now->copy();
        $daysCounted = 0;
        while ($daysCounted < 2) {
            $workdaysAgo->subDay();
            // Lewati Sabtu (6) dan Minggu (0)
            if (!in_array($workdaysAgo->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $daysCounted++;
            }
        }

        $requests = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->where(function ($query) use ($workdaysAgo) {
                $query->where(function ($q) use ($workdaysAgo) {
                    $q->whereNotNull('Ready_Request')
                    ->where('Ready_Request', '<', $workdaysAgo);
                });
                // ->orWhere(function ($q) use ($workdaysAgo) {
                //     $q->whereNotNull('Shipping_Request')
                //     ->where('Shipping_Request', '<', $workdaysAgo);
                // })
                // ->orWhere(function ($q) use ($workdaysAgo) {
                //     $q->whereNotNull('Production_Area_Request')
                //     ->where('Production_Area_Request', '<', $workdaysAgo);
                // })
                // ->orWhere(function ($q) use ($workdaysAgo) {
                //     $q->whereNotNull('Design_Changes_Request')
                //     ->where('Design_Changes_Request', '<', $workdaysAgo);
                // });
            })
            ->orderBy('Day_Request', 'desc')
            ->get();

        $formattedDate = Carbon::parse($date)->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $requests->count();

        // $correct = $requests->filter(fn($request) => $request->Correctness_Request == 1)->count();
        // $incorrect = $totalRequests - $correct;

        return view('admins.missings.index', compact('requests', 'totalRequests', 'formattedDate', 'date'));
    }

    public function export(Request $request) {
        $date = $request->input('Day_Request_Hidden');
        $date = Carbon::parse($date)->format('Y-m-d');
        $now = Carbon::now();
        // Hitung waktu 2 hari kerja lalu (tanpa Sabtu dan Minggu)
        $workdaysAgo = $now->copy();
        $daysCounted = 0;
        while ($daysCounted < 2) {
            $workdaysAgo->subDay();
            // Lewati Sabtu (6) dan Minggu (0)
            if (!in_array($workdaysAgo->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $daysCounted++;
            }
        }

        $requests = RequestModel::with('member', 'record')
            ->where('Status_Request', '!=', 'Done')
            ->where(function ($query) use ($workdaysAgo) {
                $query->where(function ($q) use ($workdaysAgo) {
                    $q->whereNotNull('Ready_Request')
                    ->where('Ready_Request', '<', $workdaysAgo);
                });
                // ->orWhere(function ($q) use ($workdaysAgo) {
                //     $q->whereNotNull('Shipping_Request')
                //     ->where('Shipping_Request', '<', $workdaysAgo);
                // })
                // ->orWhere(function ($q) use ($workdaysAgo) {
                //     $q->whereNotNull('Production_Area_Request')
                //     ->where('Production_Area_Request', '<', $workdaysAgo);
                // })
                // ->orWhere(function ($q) use ($workdaysAgo) {
                //     $q->whereNotNull('Design_Changes_Request')
                //     ->where('Design_Changes_Request', '<', $workdaysAgo);
                // });
            })
            ->orderBy('Day_Request', 'desc')
            ->get();
        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['No', 'Rack', 'Item', 'Name', 'Sum', 'Time Request', 'Ready Stock', 'Day(s)', 'Hour(s) Minute(s)', 'PIC'];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        $sheet->setAutoFilter(
            $sheet->calculateWorksheetDimension() // otomatis dari A1 sampai kolom terakhir
        );

        // Isi data
        $row = 2;
        foreach ($requests as $index => $request) {
            // === 1. Time Request ===
            $timeRequest = ($request->Day_Request ?? '') . " " . ($request->Time_Request ?? '');

            // === 2. Ready Stock (sama seperti di view) ===
            $statuses = [];
            if ($request->Ready_Request) $statuses[] = 'Ready: ' . $request->Ready_Request;
            if ($request->Shipping_Request) $statuses[] = 'Shipping: ' . $request->Shipping_Request;
            if ($request->Production_Area_Request) $statuses[] = 'Production: ' . $request->Production_Area_Request;
            if ($request->Design_Changes_Request) $statuses[] = 'Design Change: ' . $request->Design_Changes_Request;
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
                $duration = $request->getWorkingDuration($statusTimestamp);

                if ($duration['on_time']) {
                    $overdueDay = 0;
                    $overdueHM = 'On time';
                } else {
                    $overdueDay = $duration['days'] . ' day(s)';

                    $hmParts = [];
                    if ($duration['hours'] > 0) $hmParts[] = $duration['hours'] . ' hour(s)';
                    if ($duration['minutes'] > 0) $hmParts[] = $duration['minutes'] . ' minute(s)';

                    $overdueHM = implode(' ', $hmParts);
                    if (empty($overdueHM)) $overdueHM = '0 minute(s)';
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
            ], null, 'A' . $row);

            $row++;
        }

        // 🔑 Auto size kolom
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan ke file di storage/app/public
        $fileName = "Missing_List_DST_" . $date . ".xlsx";
        $filePath = storage_path('app/public/' . $fileName);

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
            $requestTime = Carbon::parse($request->Day_Request . ' ' . $request->Time_Request);

            // Jika request di masa depan, skip
            if ($requestTime->gt($now)) {
                return false;
            }

            $current = $requestTime->copy();
            $workingHours = 0;

            // Loop per jam sampai mencapai now
            while ($current->lt($now)) {
                // Lewati Sabtu (6) dan Minggu (0)
                if (!in_array($current->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                    $workingHours++;
                }
                $current->addHour();
            }

            // Jika sudah lewat 24 jam kerja → missing
            return $workingHours >= 24;
        })->values();

        $formattedDate = $now->locale('en')->isoFormat('dddd, D-MMM-YY');
        $totalRequests = $missingRequests->count();

        return view('admins.missings.mc', compact('missingRequests', 'totalRequests', 'formattedDate', 'date'));
    }

    public function missing_mc_export(Request $request) {
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
            $requestTime = Carbon::parse($request->Day_Request . ' ' . $request->Time_Request);

            // Jika request di masa depan, skip
            if ($requestTime->gt($now)) {
                return false;
            }

            $current = $requestTime->copy();
            $workingHours = 0;

            // Loop per jam sampai mencapai now
            while ($current->lt($now)) {
                // Lewati Sabtu (6) dan Minggu (0)
                if (!in_array($current->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                    $workingHours++;
                }
                $current->addHour();
            }

            // Jika sudah lewat 24 jam kerja → missing
            return $workingHours >= 24;
        })->values();

        // Buat Spreadsheet
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

        foreach ($missingRequests as $index => $request) {
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
        $fileName = "Missing_List_MC_" . $date . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/' . $fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
