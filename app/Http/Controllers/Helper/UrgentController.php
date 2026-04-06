<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Mistake;
use App\Models\Rack;
use App\Models\Record;
use App\Models\Request as RequestModel;
use App\Models\Urgent;
use App\Models\User;
use App\Models\WaQueue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UrgentController extends Controller
{
    /**
     * Display the urgents view based on the current user role layout.
     */
    public function index()
    {
        // Determine layout based on session Id_Type_User
        $layout = 'layouts.user'; // Default for member

        if (session()->has('Id_User')) {
            $typeUser = session('Id_Type_User');
            if ($typeUser == 2) {
                $layout = 'layouts.main'; // Admin
            } elseif ($typeUser == 1) {
                $layout = 'layouts.mc'; // Mc
            } elseif ($typeUser == 4) {
                $layout = 'layouts.area'; // Area
            }
        }

        return view('helpers.urgents', compact('layout'));
    }

    /**
     * Return datatables data for urgents
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $query = Urgent::with(['member', 'user', 'reporterMember', 'requestModel', 'mistake', 'record']);

            // Custom Filter logic
            if ($codeRack = $request->input('codeRack')) {
                $query->where('Code_Rack', 'LIKE', '%'.$codeRack.'%');
            }

            if ($dateUrgent = $request->input('dateUrgent')) {
                $query->where('Time_Urgent', 'LIKE', '%'.$dateUrgent.'%');
            }

            return DataTables::eloquent($query)
                ->addColumn('PIC_Urgent', function ($urgent) {
                    return $urgent->member ? $urgent->member->Name_Member : '-';
                })
                ->addColumn('Mistake_Category', function ($urgent) {
                    if (! $urgent->mistake) {
                        return '-';
                    }

                    $cat = strtolower($urgent->mistake->Category_Mistake);
                    $detail = strtolower($urgent->mistake->Manual_Category_Detail);

                    $label = strtoupper($urgent->mistake->Category_Mistake);
                    $class = 'secondary';

                    if ($cat == 'perubahan desain') {
                        $label = 'DESIGN CHANGE';
                        $class = 'warning';
                    } elseif ($cat == 'shipping') {
                        $label = 'SHIPPING';
                        $class = 'info';
                    } elseif ($cat == 'lain-lain' && $detail == 'produksi') {
                        $label = 'PRODUCTION';
                        $class = 'primary';
                    } elseif ($cat == 'telat supply' || $cat == 'telat request') {
                        $class = 'secondary';
                    }

                    return '<span class="badge badge-'.$class.'">'.$label.'</span>';
                })
                ->addColumn('Request_Details', function ($urgent) {
                    if ($urgent->requestModel) {
                        return 'Item: '.$urgent->requestModel->Code_Item_Rack.' - Sum: '.$urgent->requestModel->Sum_Request;
                    }

                    return 'N/A';
                })
                ->addColumn('Reporter', function ($urgent) {
                    if (empty($urgent->Id_Type_User)) {
                        return optional($urgent->reporterMember)->Name_Member ?? '-';
                    }

                    return optional($urgent->user)->Username_User ?? '-';
                })
                ->addColumn('Request_Time', function ($urgent) {
                    if ($urgent->requestModel) {
                        return $urgent->requestModel->Day_Request.' '.$urgent->requestModel->Time_Request;
                    }

                    return '-';
                })
                ->addColumn('Record_Time', function ($urgent) {
                    if ($urgent->record) {
                        return $urgent->record->Day_Record.' '.$urgent->record->Time_Record;
                    }

                    return '-';
                })
                ->rawColumns(['PIC_Urgent', 'Mistake_Category', 'Request_Details', 'Reporter', 'Record_Time', 'Request_Time'])
                ->make(true);
        }

        return abort(403, 'Unauthorized action.');
    }

    /**
     * Compute and return daily and monthly recap JSON for urgents.
     */
    public function getRecapData(Request $request)
    {
        $dateUrgentParam = $request->input('dateUrgent');
        if (! $dateUrgentParam) {
            $dateUrgentParam = Carbon::today()->format('Y-m-d');
        }

        try {
            $dateUrgent = Carbon::parse($dateUrgentParam);
        } catch (\Exception $e) {
            $dateUrgent = Carbon::today();
        }

        $dailyUrgents = Urgent::with(['member', 'mistake'])
            ->whereDate('Time_Urgent', $dateUrgent)
            ->get();
        $dailyMetrics = $this->calculateMetrics($dailyUrgents);

        $monthlyUrgents = Urgent::with(['member', 'mistake'])
            ->whereYear('Time_Urgent', $dateUrgent->year)
            ->whereMonth('Time_Urgent', $dateUrgent->month)
            ->get();
        $monthlyMetrics = $this->calculateMetrics($monthlyUrgents);

        return response()->json([
            'daily' => $dailyMetrics,
            'monthly' => $monthlyMetrics,
            'date_formatted' => $dateUrgent->format('d M Y'),
            'month_formatted' => $dateUrgent->format('F Y'),
        ]);
    }

    private function calculateMetrics($urgents)
    {
        $metrics = [
            'boss_mc' => ['total' => 0, 'categories' => []],
            'dst' => ['total' => 0, 'categories' => []],
        ];

        foreach ($urgents as $urgent) {
            $picName = $urgent->member ? $urgent->member->Name_Member : '-';
            $isBossMc = ($picName === 'Boss MC');

            $bucket = $isBossMc ? 'boss_mc' : 'dst';
            $metrics[$bucket]['total']++;

            $categoryLabel = '-';
            if ($urgent->mistake) {
                $cat = strtolower($urgent->mistake->Category_Mistake);
                $detail = strtolower($urgent->mistake->Manual_Category_Detail);

                if ($cat == 'perubahan desain') {
                    $categoryLabel = 'DESIGN CHANGE';
                } elseif ($cat == 'shipping') {
                    $categoryLabel = 'SHIPPING';
                } elseif ($cat == 'lain-lain' && $detail == 'produksi') {
                    $categoryLabel = 'PRODUCTION';
                } else {
                    $categoryLabel = strtoupper($cat);
                }
            }

            if (! isset($metrics[$bucket]['categories'][$categoryLabel])) {
                $metrics[$bucket]['categories'][$categoryLabel] = 0;
            }
            $metrics[$bucket]['categories'][$categoryLabel]++;
        }

        return $metrics;
    }

    /**
     * Display the urgent scan view for members.
     */
    public function scan()
    {
        return view('helpers.urgent_scan');
    }

    /**
     * Process urgent scan for members.
     */
    public function processScan(Request $request)
    {
        $codeRack = $request->input('Code_Rack');
        $idMemberLogged = session('Id_Member'); // Member ID
        $nowDate = Carbon::now()->format('Y-m-d');
        $nowTime = Carbon::now()->format('Y-m-d H:i:s');

        if (! $codeRack) {
            return redirect()->back()->with('error', 'Code Rack tidak boleh kosong');
        }

        // Check if waiting request exists
        $waitingRequest = RequestModel::where('Code_Rack', $codeRack)
            ->where('Status_Request', 'Waiting')
            ->first();

        if ($waitingRequest) {
            $idRequest = $waitingRequest->Id_Request;

            // Check if request is less than 24 hours old
            $requestTime = Carbon::parse($waitingRequest->Day_Request.' '.$waitingRequest->Time_Request);
            $isLessThan24Hours = $requestTime->diffInHours(Carbon::now()) < 24;

            if ($isLessThan24Hours) {
                // If < 24 hours, category is "telat request" and PIC is the member responsible
                $avgMemberRecord = Record::select('Id_User', DB::raw('COUNT(Id_User) as count'))
                    ->where('Code_Rack', $codeRack)
                    ->groupBy('Id_User')
                    ->orderBy('count', 'desc')
                    ->first();

                $idMemberTarget = null;
                $nameMemberTarget = null;
                if ($avgMemberRecord) {
                    $idMemberTarget = $avgMemberRecord->Id_User;
                    $member = Member::find($idMemberTarget);
                    // Check if member is inactive
                    if ($member && $member->Status_Non_Active == 1) {
                        $systemMember = Member::where('Name_Member', 'system')->first();
                        $idMemberTarget = $systemMember ? $systemMember->Id_Member : 35;
                        $nameMemberTarget = 'system';
                    } else {
                        $nameMemberTarget = $member ? $member->Name_Member : null;
                    }
                } else {
                    $systemMember = Member::where('Name_Member', 'system')->first();
                    $idMemberTarget = $systemMember ? $systemMember->Id_Member : 35;
                    $nameMemberTarget = 'system';
                }

                $category = 'telat request';
                $manualDetail = null;

                $mistake = Mistake::create([
                    'Id_Request' => $idRequest,
                    'PIC' => $nameMemberTarget,
                    'Category_Mistake' => $category,
                    'Day_Mistake' => $nowDate,
                    'Status_Mistake' => 0,
                ]);

                Urgent::create([
                    'Id_User' => $idMemberLogged,
                    'Id_Type_User' => null,
                    'Code_Rack' => $codeRack,
                    'Id_Request' => $idRequest,
                    'Id_Member' => $idMemberTarget,
                    'Time_Urgent' => $nowTime,
                    'Id_Mistake' => $mistake->Id_Mistake,
                ]);

                // Queue WA notification
                $reporter = Member::find($idMemberLogged);
                $this->queueWaMessage([
                    'time_urgent' => $nowTime,
                    'code_rack' => $codeRack,
                    'pic' => $nameMemberTarget,
                    'reporter' => $reporter ? $reporter->Name_Member : 'Member',
                    'code_item' => $waitingRequest->Code_Item_Rack,
                    'sum_request' => $waitingRequest->Sum_Request,
                    'category' => 'telat request',
                    'time_request' => $waitingRequest->Day_Request.' '.$waitingRequest->Time_Request,
                ]);
            } elseif ($waitingRequest->Ready_Request !== null) {
                // Determine target member PIC
                $avgMemberRecord = Record::select('Id_User', DB::raw('COUNT(Id_User) as count'))
                    ->where('Code_Rack', $codeRack)
                    ->groupBy('Id_User')
                    ->orderBy('count', 'desc')
                    ->first();

                $idMemberTarget = null;
                $nameMemberTarget = null;
                if ($avgMemberRecord) {
                    $idMemberTarget = $avgMemberRecord->Id_User;
                    $member = Member::find($idMemberTarget);
                    // Check if member is inactive
                    if ($member && $member->Status_Non_Active == 1) {
                        $systemMember = Member::where('Name_Member', 'system')->first();
                        $idMemberTarget = $systemMember ? $systemMember->Id_Member : 35;
                        $nameMemberTarget = 'system';
                    } else {
                        $nameMemberTarget = $member ? $member->Name_Member : null;
                    }
                } else {
                    $systemMember = Member::where('Name_Member', 'system')->first();
                    $idMemberTarget = $systemMember ? $systemMember->Id_Member : 35;
                    $nameMemberTarget = 'system';
                }

                $category = 'telat supply';
                $manualDetail = null;

                $mistake = Mistake::create([
                    'Id_Request' => $idRequest,
                    'PIC' => $nameMemberTarget,
                    'Category_Mistake' => $category,
                    'Day_Mistake' => $nowDate,
                    'Status_Mistake' => 0,
                ]);

                Urgent::create([
                    'Id_User' => $idMemberLogged, // Storing Member ID as Id_User as requested
                    'Id_Type_User' => null, // Explicitly empty for members
                    'Code_Rack' => $codeRack,
                    'Id_Request' => $idRequest,
                    'Id_Member' => $idMemberTarget,
                    'Time_Urgent' => $nowTime,
                    'Id_Mistake' => $mistake->Id_Mistake,
                ]);

                // Queue WA notification
                $reporter = Member::find($idMemberLogged);
                $this->queueWaMessage([
                    'time_urgent' => $nowTime,
                    'code_rack' => $codeRack,
                    'pic' => $nameMemberTarget,
                    'reporter' => $reporter ? $reporter->Name_Member : 'Member',
                    'code_item' => $waitingRequest->Code_Item_Rack,
                    'sum_request' => $waitingRequest->Sum_Request,
                    'category' => 'telat supply',
                    'time_request' => $waitingRequest->Day_Request.' '.$waitingRequest->Time_Request,
                ]);

            } else {
                $bossMcMember = Member::where('Name_Member', 'Boss MC')->first();
                // Check if Boss MC is inactive
                if ($bossMcMember && $bossMcMember->Status_Non_Active == 1) {
                    $systemMember = Member::where('Name_Member', 'system')->first();
                    $idBossMc = $systemMember ? $systemMember->Id_Member : 35;
                    $nameBossMc = 'system';
                } else {
                    $idBossMc = $bossMcMember ? $bossMcMember->Id_Member : 32;
                    $nameBossMc = 'Boss MC';
                }

                $category = 'telat supply mc';
                $manualDetail = null;
                if ($waitingRequest->Production_Area_Request !== null) {
                    $category = 'lain-lain';
                    $manualDetail = 'produksi';
                } elseif ($waitingRequest->Design_Changes_Request !== null) {
                    $category = 'perubahan desain';
                } elseif ($waitingRequest->Shipping_Request !== null) {
                    $category = 'shipping';
                }

                $mistake = Mistake::create([
                    'Id_Request' => $idRequest,
                    'PIC' => $nameBossMc,
                    'Category_Mistake' => $category,
                    'Manual_Category_Detail' => $manualDetail,
                    'Day_Mistake' => $nowDate,
                    'Status_Mistake' => 0,
                ]);

                Urgent::create([
                    'Id_User' => $idMemberLogged,
                    'Id_Type_User' => null,
                    'Code_Rack' => $codeRack,
                    'Id_Request' => $idRequest,
                    'Id_Member' => $idBossMc,
                    'Time_Urgent' => $nowTime,
                    'Id_Mistake' => $mistake->Id_Mistake,
                ]);

                // Queue WA notification
                $reporter = Member::find($idMemberLogged);
                $this->queueWaMessage([
                    'time_urgent' => $nowTime,
                    'code_rack' => $codeRack,
                    'pic' => $nameBossMc,
                    'reporter' => $reporter ? $reporter->Name_Member : 'Member',
                    'code_item' => $waitingRequest->Code_Item_Rack,
                    'sum_request' => $waitingRequest->Sum_Request,
                    'category' => $category,
                    'time_request' => $waitingRequest->Day_Request.' '.$waitingRequest->Time_Request,
                ]);
            }

            $pic = ($isLessThan24Hours || $waitingRequest->Ready_Request !== null) ? $nameMemberTarget : $nameBossMc;

            $cat = strtolower($category ?? 'telat supply');
            $mDetail = strtolower($manualDetail ?? '');

            $displayCategory = strtoupper($cat);
            $badgeClass = 'secondary';

            if ($cat == 'perubahan desain') {
                $displayCategory = 'DESIGN CHANGE';
                $badgeClass = 'warning';
            } elseif ($cat == 'shipping') {
                $displayCategory = 'SHIPPING';
                $badgeClass = 'info';
            } elseif ($cat == 'lain-lain' && $mDetail == 'produksi') {
                $displayCategory = 'PRODUCTION';
                $badgeClass = 'primary';
            } elseif ($cat == 'telat supply' || $cat == 'telat request' || $cat == 'telat supply mc' || $cat == 'telat supply mc') {
                $badgeClass = 'secondary';
            }

            $scanSuccessData = [
                'category' => $displayCategory,
                'badge_class' => $badgeClass,
                'time_request' => $waitingRequest->Time_Request,
                'sum_request' => $waitingRequest->Sum_Request,
                'pic' => $pic,
                'code_rack' => $codeRack,
            ];

            return redirect()->back()->with([
                'success' => 'Urgent Scan Code Rack '.$codeRack.' berhasil diproses.',
                'scan_success_data' => $scanSuccessData,
            ]);

        } else {
            $avgMemberReq = RequestModel::select('Id_User', DB::raw('COUNT(Id_User) as count'))
                ->where('Code_Rack', $codeRack)
                ->groupBy('Id_User')
                ->orderBy('count', 'desc')
                ->first();

            $idMemberTarget = null;
            $nameMemberTarget = null;
            if ($avgMemberReq) {
                $idMemberTarget = $avgMemberReq->Id_User;
                $member = Member::find($idMemberTarget);
                // Check if member is inactive
                if ($member && $member->Status_Non_Active == 1) {
                    $systemMember = Member::where('Name_Member', 'system')->first();
                    $idMemberTarget = $systemMember ? $systemMember->Id_Member : 35;
                    $nameMemberTarget = 'system';
                } else {
                    $nameMemberTarget = $member ? $member->Name_Member : null;
                }
            } else {
                $systemMember = Member::where('Name_Member', 'system')->first();
                $idMemberTarget = $systemMember ? $systemMember->Id_Member : 35;
                $nameMemberTarget = 'system';
            }

            $lastReq = RequestModel::where('Code_Rack', $codeRack)->orderBy('Id_Request', 'desc')->first();
            $sumRequest = 1;
            if ($lastReq && $lastReq->Sum_Request) {
                $sumRequest = $lastReq->Sum_Request;
            }

            $rack = Rack::where('Code_Rack', $codeRack)->first();
            $codeItemRack = $rack ? $rack->Code_Item_Rack : ($lastReq ? $lastReq->Code_Item_Rack : null);

            if (! $codeItemRack) {
                return redirect()->back()->with('error', 'Kode Rack "'.$codeRack.'" tidak ditemukan di Data Rack.');
            }

            $newReq = new RequestModel;
            $newReq->Day_Request = $nowDate;
            $newReq->Time_Request = $nowTime;
            $newReq->Code_Item_Rack = $codeItemRack;
            $newReq->Code_Rack = $codeRack;
            $newReq->Id_User = $idMemberTarget;
            $newReq->Status_Request = 'Waiting';
            $newReq->Sum_Request = $sumRequest;
            $newReq->Urgent_Request = 1;
            $newReq->save();

            $idRequestNew = $newReq->Id_Request;

            $mistake = Mistake::create([
                'Id_Request' => $idRequestNew,
                'PIC' => $nameMemberTarget,
                'Category_Mistake' => 'telat request',
                'Day_Mistake' => $nowDate,
                'Status_Mistake' => 0,
            ]);

            Urgent::create([
                'Id_User' => $idMemberLogged,
                'Id_Type_User' => null,
                'Code_Rack' => $codeRack,
                'Id_Request' => $idRequestNew,
                'Id_Member' => $idMemberTarget,
                'Time_Urgent' => $nowTime,
                'Id_Mistake' => $mistake->Id_Mistake,
            ]);

            // Queue WA notification
            $reporter = Member::find($idMemberLogged);
            $this->queueWaMessage([
                'time_urgent' => $nowTime,
                'code_rack' => $codeRack,
                'pic' => $nameMemberTarget,
                'reporter' => $reporter ? $reporter->Name_Member : 'Member',
                'code_item' => $codeItemRack,
                'sum_request' => $sumRequest,
                'category' => 'telat request',
                'time_request' => $nowTime,
            ]);

            $scanSuccessData = [
                'category' => 'TELAT REQUEST',
                'badge_class' => 'secondary',
                'time_request' => $nowTime,
                'sum_request' => $sumRequest,
                'pic' => $nameMemberTarget,
                'code_rack' => $codeRack,
            ];

            return redirect()->back()->with([
                'success' => 'Urgent Scan Code Rack '.$codeRack.' berhasil diproses.',
                'scan_success_data' => $scanSuccessData,
            ]);
        }
    }

    /**
     * Format and save a WA notification message to the queue.
     */
    private function queueWaMessage(array $data): void
    {
        $message = "URGENT SCAN ALERT\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Time Urgent: {$data['time_urgent']}\n";
        $message .= "Time Request: {$data['time_request']}\n";
        $category = strtoupper($data['category']);
        if ($data['category'] == 'telat supply' || $data['category'] == 'telat request') {
            $category .= ' DST';
        } elseif ($data['category'] == 'shipping' || $data['category'] == 'perubahan desain') {
            $category .= ' - MC';
        } elseif ($data['category'] == 'lain-lain' || $data['category'] == 'production') {
            $category = 'PRODUCTION - MC';
        }

        $message .= 'Category: ' . $category . "\n";
        $message .= "Code Rack: {$data['code_rack']}\n";
        $message .= "PIC: {$data['pic']}\n";
        $message .= "Reporter: {$data['reporter']}\n";
        $message .= "Request Details:\n";
        $message .= "Item: {$data['code_item']} - Sum: {$data['sum_request']}";

        WaQueue::create([
            'message' => $message,
            'group_id' => '120363045467407165@g.us',
            'status' => 'pending',
        ]);
    }
}
