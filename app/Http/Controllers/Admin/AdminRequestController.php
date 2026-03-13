<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;
use App\Models\Member;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Yajra\DataTables\Facades\DataTables;

class AdminRequestController extends Controller
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
            $query->whereIn('Id_User', $memberIds);
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

        return view('admins.requests.index', compact(
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

        return view('admins.requests.index', compact(
            'requests', 'totalRequest', 'correct', 'incorrect', 'formattedDate', 'date', 'dateForInput', 'members'
        ));
    }

    public function export(Request $request)
    {
        $date = Carbon::parse($request->input('Day_Request_Hidden'))->format('Y-m-d');
        $memberIds = $request->input('Id_User', []);

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
            'No', 'Time Request', 'Area', 'Rack', 'Sum Request', 'Urgenity', 'Item', 'Name', 
            "1=Ready,2=Ship,\n3=Prod,4=Design", 'Time Record', 'Sum Record', 'Member Request', 
            'Member Record', 'Updated'
            ];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']]
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);
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

            $sheet->fromArray([
                $no,
                $timeRequest,
                $request->Area_Request ?? '',
                $request->Code_Rack,
                $request->Sum_Request,
                $request->Urgent_Request == 1 ? '✓' : '',
                $request->Code_Item_Rack,
                $request->rack->Name_Item_Rack ?? '',
                $readyStockDisplay,
                $timeRecord,
                optional($request->record)->Sum_Record ?? '',
                $request->member->Name_Member ?? '',
                optional($request->record)->member->Name_Member ?? '',
                $request->Updated_At_Request,
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
                return '<span title="'.e($type).'">'.e($short).'</span>';
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
        return view('admins.requests.search', compact('members'));
    }
}
