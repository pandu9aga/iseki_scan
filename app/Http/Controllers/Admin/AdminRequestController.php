<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Request as RequestModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

        if (! empty($memberIds)) {
            $this->applyMemberFilter($query, $memberIds);
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

        $members = $this->getPeople();

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

        if (! empty($memberIds)) {
            $this->applyMemberFilter($query, $memberIds);
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

        $members = $this->getPeople();

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

        if (! empty($memberIds)) {
            $this->applyMemberFilter($query, $memberIds);
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
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'No', 'Time Request', 'Area', 'Rack', 'Sum Request', 'Urgenity', 'Item', 'Name',
            "1=Ready,2=Ship,\n3=Prod,4=Design", 'Sum Stock', 'Ready Stock', 'Estimation Date', 'Time Record', 'Sum Record', 'Member Request',
            'Member Record', 'Updated',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        // Style header (tebal & background abu-abu)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
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
                $prefix = $request->Ok_Stock == 1 ? 'OK Shipping: ' : 'Shipping: ';
                $readyDisplay[] = $prefix.$request->Shipping_Request;
            }
            if ($request->Production_Area_Request) {
                $readyDisplay[] = 'Production: '.$request->Production_Area_Request;
            }
            if ($request->Design_Changes_Request) {
                $prefix = $request->Ok_Stock == 1 ? 'OK Design: ' : 'Design: ';
                $readyDisplay[] = $prefix.$request->Design_Changes_Request;
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

            $sumStockDisplay = '';
            if ($statusCode == '2' || $statusCode == '4') {
                $sumStockDisplay = $request->Stock_Shipping ?? '';
            } else {
                $sumStockDisplay = $request->Sum_Stock ?? '';
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
                $sumStockDisplay,
                $readyStockDisplay,
                $estimationDisplay,
                $timeRecord,
                optional($request->record)->Sum_Record ?? '',
                $request->Is_User == 1 ? (optional($request->user)->Name_User ?? 'Admin') : ($request->member->Name_Member ?? ''),
                optional($request->record)->Is_User == 1 ? (optional($request->record->user)->Name_User ?? 'Admin') : (optional($request->record)->member->Name_Member ?? ''),
                $request->Updated_At_Request,
            ], null, 'A'.$row);

            $lastUser = $request->Id_User;
            $no++;
            $row++;
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
        $fileName = 'Request_'.$date.'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/'.$fileName);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportSearch(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required',
        ]);

        $startDate = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $endDate = Carbon::parse($request->input('end_date'))->format('Y-m-d');
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
        $spreadsheet = new Spreadsheet;
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
            'Id',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ];
        $sheet->getStyle('A1:R1')->applyFromArray($headerStyle);
        $sheet->getStyle('A1:R1')->getAlignment()->setWrapText(true);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $row = 2;
        $lastUser = null;
        $no = 1;

        foreach ($requests as $req) {
            if ($lastUser !== null && $lastUser != $req->Id_User) {
                $sheet->fromArray(array_fill(0, 18, '-'), null, 'A'.$row);
                $row++;
                $no = 1;
            }

            $readyDisplay = [];
            if ($req->Ready_Request) {
                $readyDisplay[] = 'Ready: '.$req->Ready_Request;
            }
            if ($req->Shipping_Request) {
                $prefix = $req->Ok_Stock == 1 ? 'OK Shipping: ' : 'Shipping: ';
                $readyDisplay[] = $prefix.$req->Shipping_Request;
            }
            if ($req->Production_Area_Request) {
                $readyDisplay[] = 'Production: '.$req->Production_Area_Request;
            }
            if ($req->Design_Changes_Request) {
                $prefix = $req->Ok_Stock == 1 ? 'OK Design: ' : 'Design: ';
                $readyDisplay[] = $prefix.$req->Design_Changes_Request;
            }
            $readyStockDisplay = implode(' | ', $readyDisplay);

            $timeRequest = ($req->Day_Request ?? '').' '.($req->Time_Request ?? '');
            $timeRecord = ($req->record->Day_Record ?? '').' '.($req->record->Time_Record ?? '');

            $statusCode = '';
            if ($req->Ready_Request !== null) {
                $statusCode = '1';
            } elseif ($req->Shipping_Request !== null) {
                $statusCode = '2';
            } elseif ($req->Production_Area_Request !== null) {
                $statusCode = '3';
            } elseif ($req->Design_Changes_Request !== null) {
                $statusCode = '4';
            }

            $sumStockDisplay = '';
            if ($statusCode == '2' || $statusCode == '4') {
                $sumStockDisplay = $req->Stock_Shipping ?? '';
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
            ], null, 'A'.$row);

            $lastUser = $req->Id_User;
            $no++;
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 2) {
            $columnsToCenter = ['E', 'F', 'I', 'J', 'K', 'N'];
            foreach ($columnsToCenter as $col) {
                $sheet->getStyle($col.'2:'.$col.$lastRow)
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
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/public/'.$fileName);
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
                    return $r->Day_Request.' '.$r->Time_Request;
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
                            return '<span class="badge badge-secondary">'.e($status).'</span>';
                    }
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
                ->editColumn('Sum_Stock', function ($r) {
                    if ($r->Shipping_Request || $r->Design_Changes_Request) {
                        return $r->Stock_Shipping ?? '';
                    }

                    return $r->Sum_Stock ?? '';
                })
                ->addColumn('Estimation_Stock', function ($r) {
                    if ($r->Estimation_Stock) {
                        return \Carbon\Carbon::parse($r->Estimation_Stock)->format('d/m/Y');
                    }

                    return '-';
                })
                ->addColumn('ready_status_display', function ($r) {
                    $statuses = [];
                    if ($r->Ready_Request) {
                        $statuses[] = '<span class="badge badge-success">Ready</span>: '.$r->Ready_Request;
                    }
                    if ($r->Shipping_Request) {
                        $title = $r->Ok_Stock == 1 ? 'OK Shipping' : 'Shipping';
                        $statuses[] = '<span class="badge badge-info">'.$title.'</span>: '.$r->Shipping_Request;
                    }
                    if ($r->Production_Area_Request) {
                        $statuses[] = '<span class="badge badge-primary">Production</span>: '.$r->Production_Area_Request;
                    }
                    if ($r->Design_Changes_Request) {
                        $title = $r->Ok_Stock == 1 ? 'OK Design Change' : 'Design Change';
                        $statuses[] = '<span class="badge badge-warning">'.$title.'</span>: '.$r->Design_Changes_Request;
                    }

                    return implode(' | ', $statuses);
                })
                ->filterColumn('Id_User', function ($query, $keyword) {
                    if ($keyword !== '') {
                        $this->applyMemberFilter($query, [$keyword]);
                    }
                })
                ->orderColumn('Day_Request', function ($query, $order) {
                    $query->orderBy('Day_Request', $order)->orderBy('Time_Request', $order);
                })
                ->orderColumn('ready_status_display', function ($query, $order) {
                    $query->orderByRaw('GREATEST(COALESCE(Ready_Request, "1000-01-01"), COALESCE(Shipping_Request, "1000-01-01"), COALESCE(Production_Area_Request, "1000-01-01"), COALESCE(Design_Changes_Request, "1000-01-01")) '.$order);
                })
                ->rawColumns(['Urgent_Request', 'ready_status_display', 'Status_Request_Display', 'Type_Tractor_Rack'])
                ->make(true);
        }

        // Non-AJAX: kirim daftar member ke view
        $members = $this->getPeople();

        return view('admins.requests.search', compact('members'));
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

    private function applyMemberFilter($query, $memberIds)
    {
        $memberIds = array_filter($memberIds, function($value) { return $value !== null && $value !== ''; });
        if (empty($memberIds)) {
            return;
        }

        $query->where(function ($q) use ($memberIds) {
            foreach ($memberIds as $id) {
                if (strpos($id, 'u_') === 0) {
                    $originalId = substr($id, 2);
                    $q->orWhere(function ($sq) use ($originalId) {
                        $sq->where('Id_User', $originalId)->where('Is_User', 1);
                    });
                } else {
                    $originalId = strpos($id, 'm_') === 0 ? substr($id, 2) : $id;
                    $q->orWhere(function ($sq) use ($originalId) {
                        $sq->where('Id_User', $originalId)->where(function($q2) {
                            $q2->where('Is_User', 0)->orWhereNull('Is_User');
                        });
                    });
                }
            }
        });
    }
}
