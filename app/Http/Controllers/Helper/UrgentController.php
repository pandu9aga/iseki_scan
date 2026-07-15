<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Mistake;
use App\Models\Rack;
use App\Models\Record;
use App\Models\Request as RequestModel;
use App\Models\StockItem;
use App\Models\Urgent;
use App\Models\User;
use App\Models\WaQueue;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
            $query = Urgent::with(['member', 'user', 'reporterMember', 'requestModel.rack', 'withdrawal.rack', 'mistake', 'record'])
                ->orderBy('Time_Urgent', 'desc');

            // Custom Filter logic
            if ($codeRack = $request->input('codeRack')) {
                $query->where('Code_Rack', 'LIKE', '%'.$codeRack.'%');
            }

            if ($dateUrgent = $request->input('dateUrgent')) {
                $query->where('Time_Urgent', 'LIKE', '%'.$dateUrgent.'%');
            }

            if ($keyword = $request->input('keyword')) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('Code_Rack', 'LIKE', "%$keyword%")
                        ->orWhereHas('member', function ($q2) use ($keyword) {
                            $q2->where('Name_Member', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('mistake', function ($q2) use ($keyword) {
                            $q2->where('Category_Mistake', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('reporterMember', function ($q2) use ($keyword) {
                            $q2->where('Name_Member', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('user', function ($q2) use ($keyword) {
                            $q2->where('Username_User', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('requestModel.rack', function ($q2) use ($keyword) {
                            $q2->where('Name_Item_Rack', 'LIKE', "%$keyword%")
                                ->orWhere('Code_Item_Rack', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('withdrawal.rack', function ($q2) use ($keyword) {
                            $q2->where('Name_Item_Rack', 'LIKE', "%$keyword%")
                                ->orWhere('Code_Item_Rack', 'LIKE', "%$keyword%");
                        });
                });
            }

            return DataTables::eloquent($query)
                ->addColumn('PIC_Urgent', function ($urgent) {
                    // Check if this is a QC mistake
                    if ($urgent->mistake && strtolower($urgent->mistake->Category_Mistake) === 'telat qc') {
                        return 'QC';
                    }

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
                    } elseif ($cat == 'telat qc') {
                        $label = 'TELAT QC';
                        $class = 'pink';
                    } elseif ($cat == 'telat return rack') {
                        $label = 'TELAT RETURN RACK';
                        $class = 'secondary';
                    } elseif ($cat == 'stock') {
                        $label = 'STOCK';
                        $class = 'secondary';
                    } elseif ($cat == 'telat supply' || $cat == 'telat request') {
                        $class = 'secondary';
                    }

                    return '<span class="badge badge-'.$class.'">'.$label.'</span>';
                })
                ->addColumn('Type_Tractor', function ($urgent) {
                    if ($urgent->mistake && $urgent->mistake->Is_Withdrawal) {
                        return optional(optional($urgent->withdrawal)->rack)->Type_Tractor_Rack ?? '-';
                    }
                    return optional(optional($urgent->requestModel)->rack)->Type_Tractor_Rack ?? '-';
                })
                ->addColumn('Name_Part', function ($urgent) {
                    if ($urgent->mistake && $urgent->mistake->Is_Withdrawal) {
                        return optional(optional($urgent->withdrawal)->rack)->Name_Item_Rack ?? '-';
                    }
                    return optional(optional($urgent->requestModel)->rack)->Name_Item_Rack ?? '-';
                })
                ->addColumn('Request_Details', function ($urgent) {
                    if ($urgent->mistake && $urgent->mistake->Is_Withdrawal) {
                        if ($urgent->withdrawal) {
                            return 'Item: '.$urgent->withdrawal->Code_Item_Withdrawal.' - (Withdrawal)';
                        }
                        return 'N/A';
                    }
                    if ($urgent->requestModel) {
                        return 'Item: '.$urgent->requestModel->Code_Item_Rack.' - Sum: '.$urgent->requestModel->Sum_Request;
                    }

                    return 'N/A';
                })
                ->addColumn('Reporter', function ($urgent) {
                    if ($urgent->Is_Marshalling) {
                        $employee = DB::connection('rifa')->table('employees')->find($urgent->Id_User);
                        $name = $employee ? $employee->nama : 'Marshalling User';
                        $seq = $urgent->Sequence_No_Record ? '<br>Sequence: '.e($urgent->Sequence_No_Record) : '';
                        return e($name).$seq;
                    }

                    if (empty($urgent->Id_Type_User)) {
                        return optional($urgent->reporterMember)->Name_Member ?? '-';
                    }

                    return optional($urgent->user)->Username_User ?? '-';
                })
                ->addColumn('Request_Time', function ($urgent) {
                    if ($urgent->mistake && $urgent->mistake->Is_Withdrawal) {
                        if ($urgent->withdrawal) {
                            return $urgent->withdrawal->Date_Withdrawal ? $urgent->withdrawal->Date_Withdrawal->format('Y-m-d H:i:s') : '-';
                        }
                        return '-';
                    }
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
                ->rawColumns(['PIC_Urgent', 'Name_Part', 'Mistake_Category', 'Request_Details', 'Reporter', 'Record_Time', 'Request_Time'])
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

        $dailyUrgents = Urgent::with(['member', 'mistake', 'user', 'reporterMember'])
            ->whereDate('Time_Urgent', $dateUrgent)
            ->get();
        $dailyMetrics = $this->calculateMetrics($dailyUrgents);

        $monthlyUrgents = Urgent::with(['member', 'mistake', 'user', 'reporterMember'])
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
            'qc' => ['total' => 0, 'categories' => []],
            'dst' => ['total' => 0, 'categories' => []],
            'reporters' => [],
            'reporters_total' => 0,
        ];

        foreach ($urgents as $urgent) {
            $picName = $urgent->member ? $urgent->member->Name_Member : '-';
            $isBossMc = ($picName === 'Boss MC');

            // Check if this is a QC mistake
            $isQc = false;
            if ($urgent->mistake) {
                $cat = strtolower($urgent->mistake->Category_Mistake);
                if ($cat === 'telat qc') {
                    $isQc = true;
                }
            }

            if ($isQc) {
                $bucket = 'qc';
            } elseif ($isBossMc) {
                $bucket = 'boss_mc';
            } else {
                $bucket = 'dst';
            }
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

            // Reporter logic
            $reporterName = '-';
            if ($urgent->Is_Marshalling) {
                $employee = DB::connection('rifa')->table('employees')->find($urgent->Id_User);
                $reporterName = $employee ? $employee->nama : 'Marshalling User';
            } elseif (empty($urgent->Id_Type_User)) {
                $reporterName = $urgent->reporterMember ? $urgent->reporterMember->Name_Member : '-';
            } else {
                $reporterName = $urgent->user ? $urgent->user->Username_User : '-';
            }

            if (!isset($metrics['reporters'][$reporterName])) {
                $metrics['reporters'][$reporterName] = 0;
            }
            $metrics['reporters'][$reporterName]++;
            $metrics['reporters_total']++;
        }

        arsort($metrics['reporters']);

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

        // Mencegah double input dalam rentang waktu 5 detik
        $recentUrgent = Urgent::where('Code_Rack', $codeRack)
            ->where('Time_Urgent', '>=', Carbon::now()->subSeconds(5)->format('Y-m-d H:i:s'))
            ->first();

        if ($recentUrgent) {

            return redirect()->back()->with('success', 'Scan sedang diproses atau sudah berhasil (Double Input dicegah).');
        }

        // Helper function to check for duplicates today during business hours (07:00 - 17:00)
        $checkDuplicateToday = function () use ($codeRack, $idMemberLogged) {
            if (Carbon::now()->between('07:00', '17:00')) {
                $exists = Urgent::where('Code_Rack', $codeRack)
                    ->whereDate('Time_Urgent', Carbon::today())
                    ->where('Id_User', $idMemberLogged)
                    ->whereNull('Id_Type_User') // In UrgentController, reporter is always a Member
                    ->exists();

                if ($exists) {
                    return true;
                }
            }

            return false;
        };

        // Check for QC withdrawal and Stock item overrides
        $qcOverride = false;
        $telatReturnRackOverride = false;
        $stockOverride = false;
        $rackForOverride = Rack::where('Code_Rack', $codeRack)->first();
        if ($rackForOverride) {
            // Check if item is in active QC withdrawal (not yet returned)
            $activeWithdrawal = Withdrawal::where('Code_Item_Withdrawal', $rackForOverride->Code_Item_Rack)
                ->whereNull('Date_Return')
                ->orderBy('Id_Withdrawal', 'desc')
                ->first();
            if ($activeWithdrawal) {
                if ($activeWithdrawal->Oke_Receiving && ! $activeWithdrawal->Finish_Receiving) {
                    // Hanya salah QC jika status sudah "diterima QC" tapi belum selesai
                    $qcOverride = true;
                } elseif ($activeWithdrawal->Finish_Receiving) {
                    // QC sudah selesai tapi belum dikembalikan DST = salah DST
                    $telatReturnRackOverride = true;
                }
                // Jika belum diterima QC (Oke_Receiving = false), jangan override
                // Biarkan jatuh ke logika request status biasa (telat supply, telat mc, dll)
            }
        }
        // Check if Code_Rack is in stock_items
        $stockItemCheck = StockItem::where('Code_Rack_Stock_Item', $codeRack)->first();
        if ($stockItemCheck) {
            $stockOverride = true;
        }

        // Cari waiting request yang ada (TIDAK buat baru di sini)
        $waitingRequest = RequestModel::where('Code_Rack', $codeRack)
            ->where('Status_Request', 'Waiting')
            ->first();

        // ============================================================
        // PRIORITAS 1: Stock → PRIORITAS 2: QC/Return Rack
        // Jika terdeteksi, langsung proses & return (early return)
        // ============================================================
        if (($stockOverride && $waitingRequest) || $qcOverride || ($telatReturnRackOverride && $waitingRequest)) {
            if ($stockOverride) {
                $category = 'stock';
                [$idMemberTarget, $nameMemberTarget] = $this->getLastRecordPic($codeRack);
                $displayCategory = 'STOCK';
                $badgeClass = 'secondary';
            } elseif ($qcOverride) {
                $category = 'telat qc';
                $systemMemberQc = Member::where('Name_Member', 'system')->first();
                $idMemberTarget = $systemMemberQc ? $systemMemberQc->Id_Member : 35;
                $nameMemberTarget = 'QC';
                $displayCategory = 'TELAT QC';
                $badgeClass = 'pink';
            } else {
                $category = 'telat return rack';
                [$idMemberTarget, $nameMemberTarget] = $this->getLastRecordPic($codeRack);
                $displayCategory = 'TELAT RETURN RACK';
                $badgeClass = 'secondary';
            }

            if ($checkDuplicateToday()) {
                return redirect()->back()->with('error', 'Double Input dicegah (Sudah ada scan untuk Kode Rak oleh Anda hari ini).');
            }

            $namePart = $rackForOverride ? ($rackForOverride->Name_Item_Rack ?? '-') : '-';
            $codeItem = $rackForOverride ? ($rackForOverride->Code_Item_Rack ?? '-') : '-';

            $idRequest = $waitingRequest ? $waitingRequest->Id_Request : null;
            $isWithdrawal = false;

            if ($qcOverride && $activeWithdrawal) {
                $idRequest = $activeWithdrawal->Id_Withdrawal;
                $isWithdrawal = true;
            }

            $mistake = Mistake::create([
                'Id_Request' => $idRequest,
                'PIC' => $nameMemberTarget,
                'Category_Mistake' => $category,
                'Day_Mistake' => $nowDate,
                'Status_Mistake' => 0,
                'Is_Withdrawal' => $isWithdrawal,
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

            $reporter = Member::find($idMemberLogged);
            $this->queueWaMessage([
                'time_urgent' => $nowTime,
                'code_rack' => $codeRack,
                'pic' => $nameMemberTarget,
                'reporter' => $reporter ? $reporter->Name_Member : 'Member',
                'code_item' => $codeItem,
                'name_part' => $namePart,
                'sum_request' => $waitingRequest ? $waitingRequest->Sum_Request : '-',
                'category' => $category,
                'time_request' => $waitingRequest ? ($waitingRequest->Day_Request.' '.$waitingRequest->Time_Request) : '-',
            ]);

            return redirect()->back()->with([
                'success' => 'Urgent Scan Code Rack '.$codeRack.' berhasil diproses.',
                'scan_success_data' => [
                    'category' => $displayCategory,
                    'badge_class' => $badgeClass,
                    'time_request' => $waitingRequest ? $waitingRequest->Time_Request : $nowTime,
                    'sum_request' => $waitingRequest ? $waitingRequest->Sum_Request : '-',
                    'pic' => $nameMemberTarget,
                    'code_rack' => $codeRack,
                ],
            ]);
        }

        // ============================================================
        // PRIORITAS 3: Cek Status Request (seperti biasa, tanpa override)
        // ============================================================
        if ($waitingRequest) {
            // Get Part Name for this rack
            $rackModel = Rack::where('Code_Rack', $codeRack)->first();
            $namePart = $rackModel ? ($rackModel->Name_Item_Rack ?? '-') : '-';
            $idRequest = $waitingRequest->Id_Request;

            $requestDate = Carbon::parse($waitingRequest->Day_Request)->startOfDay();
            $urgentDate = Carbon::parse($nowDate)->startOfDay();
            $readyDate = $waitingRequest->Ready_Request !== null ? Carbon::parse($waitingRequest->Ready_Request)->startOfDay() : null;

            $readyFast = $readyDate !== null && $readyDate->lessThan($requestDate->copy()->addWeekdays(2));
            $pastReady1 = $readyDate !== null && $urgentDate->greaterThanOrEqualTo($readyDate->copy()->addWeekdays(1));
            $pastReq2 = $urgentDate->greaterThanOrEqualTo($requestDate->copy()->addWeekdays(2));

            if ($readyFast && $pastReady1) {
                // MC cepat bikin ready (< request+2wd), barang siap ≥ 1 hari kerja → supplier tidak ambil
                [$idMemberTarget, $nameMemberTarget] = $this->getLastRecordPic($codeRack);

                if ($checkDuplicateToday($idMemberTarget)) {
                    return redirect()->back()->with('error', 'Double Input dicegah (Sudah ada scan untuk Kode Rak & PIC yang sama hari ini).');
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
                    'name_part' => $namePart,
                    'sum_request' => $waitingRequest->Sum_Request,
                    'category' => $category,
                    'time_request' => $waitingRequest->Day_Request.' '.$waitingRequest->Time_Request,
                ]);

                $pic = $nameMemberTarget;

            } elseif (($readyDate !== null && !$readyFast) || ($readyDate === null && $pastReq2)) {
                // MC lambat (Ready ≥ request+2wd) ATAU Ready tidak pernah dibuat & urgent ≥ request+2wd
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

                if ($checkDuplicateToday()) {
                    return redirect()->back()->with('error', 'Double Input dicegah (Sudah ada scan untuk Kode Rak oleh Anda hari ini).');
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
                    'name_part' => $namePart,
                    'sum_request' => $waitingRequest->Sum_Request,
                    'category' => $category,
                    'time_request' => $waitingRequest->Day_Request.' '.$waitingRequest->Time_Request,
                ]);

                $pic = $nameBossMc;

            } else {
                // Same business day or next business day → telat request
                [$idMemberTarget, $nameMemberTarget] = $this->getLastRequestPic($codeRack);

                if ($checkDuplicateToday($idMemberTarget)) {
                    return redirect()->back()->with('error', 'Double Input dicegah (Sudah ada scan untuk Kode Rak & PIC yang sama hari ini).');
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
                    'name_part' => $namePart,
                    'sum_request' => $waitingRequest->Sum_Request,
                    'category' => $category,
                    'time_request' => $waitingRequest->Day_Request.' '.$waitingRequest->Time_Request,
                ]);

                $pic = $nameMemberTarget;
            }

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
            } elseif ($cat == 'telat supply' || $cat == 'telat request' || $cat == 'telat supply mc') {
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
            [$idMemberTarget, $nameMemberTarget] = $this->getLastRequestPic($codeRack);

            if ($checkDuplicateToday()) {
                return redirect()->back()->with('error', 'Double Input dicegah (Sudah ada scan untuk Kode Rak oleh Anda hari ini).');
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

            $systemMember = Member::where('Name_Member', 'system')->first();
            $idSystem = $systemMember ? $systemMember->Id_Member : 35;

            $newReq = new RequestModel;
            $newReq->Day_Request = $nowDate;
            $newReq->Time_Request = $nowTime;
            $newReq->Code_Item_Rack = $codeItemRack;
            $newReq->Code_Rack = $codeRack;
            $newReq->Id_User = $idSystem;
            $newReq->Status_Request = 'Waiting';
            $newReq->Sum_Request = $sumRequest;
            $newReq->Urgent_Request = 1;
            $newReq->save();

            $idRequestNew = $newReq->Id_Request;

            $urgentCategory = 'telat request';

            $mistake = Mistake::create([
                'Id_Request' => $idRequestNew,
                'PIC' => $nameMemberTarget,
                'Category_Mistake' => $urgentCategory,
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
            $namePart = $rack ? ($rack->Name_Item_Rack ?? '-') : '-';
            $this->queueWaMessage([
                'time_urgent' => $nowTime,
                'code_rack' => $codeRack,
                'pic' => $nameMemberTarget,
                'reporter' => $reporter ? $reporter->Name_Member : 'Member',
                'code_item' => $codeItemRack,
                'name_part' => $namePart,
                'sum_request' => $sumRequest,
                'category' => $urgentCategory,
                'time_request' => $nowTime,
            ]);

            $dispCat = 'TELAT REQUEST';
            $dispBadge = 'secondary';

            $scanSuccessData = [
                'category' => $dispCat,
                'badge_class' => $dispBadge,
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
        $category = strtoupper($data['category']);
        if ($data['category'] == 'telat supply' || $data['category'] == 'telat request') {
            $category .= ' DST';
        } elseif ($data['category'] == 'shipping' || $data['category'] == 'perubahan desain') {
            $category .= ' - MC';
        } elseif ($data['category'] == 'lain-lain' || $data['category'] == 'production') {
            $category = 'PRODUCTION - MC';
        } elseif ($data['category'] == 'telat qc') {
            $category = 'TELAT QC';
        } elseif ($data['category'] == 'telat return rack') {
            $category = 'TELAT RETURN RACK - DST';
        } elseif ($data['category'] == 'stock') {
            $category = 'STOCK - DST';
        }
        $message = "⚠️ *{$data['code_rack']}* *{$category}*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Time Urgent: {$data['time_urgent']}\n";
        $message .= "Time Request: {$data['time_request']}\n";
        $message .= "PIC: {$data['pic']}\n";
        $message .= "Reporter: {$data['reporter']}\n";
        $message .= "Request Details:\n";
        $namePart = $data['name_part'] ?? '-';
        $message .= "Item: {$data['code_item']} ({$namePart}) - Sum: {$data['sum_request']}";

        WaQueue::create([
            'message' => $message,
            'group_id' => '120363045467407165@g.us',
            'status' => 'pending',
        ]);
    }

    /**
     * Get the latest PIC for a rack based on the most recent record.
     */
    private function getLastRecordPic($codeRack)
    {
        $lastRecord = \App\Models\Record::where('Code_Rack', $codeRack)
            ->orderBy('Day_Record', 'desc')
            ->orderBy('Time_Record', 'desc')
            ->first();

        $targetUserId = $lastRecord ? $lastRecord->Id_User : null;

        return $this->resolvePicFromUserId($targetUserId);
    }

    /**
     * Get the latest PIC for a rack based on the most recent request.
     */
    private function getLastRequestPic($codeRack)
    {
        $lastRequest = RequestModel::where('Code_Rack', $codeRack)
            ->orderBy('Day_Request', 'desc')
            ->orderBy('Time_Request', 'desc')
            ->first();

        $targetUserId = $lastRequest ? $lastRequest->Id_User : null;

        return $this->resolvePicFromUserId($targetUserId);
    }

    private function resolvePicFromUserId($targetUserId)
    {
        $idMemberTarget = null;
        $nameMemberTarget = null;

        if ($targetUserId) {
            $member = Member::find($targetUserId);
            if ($member && $member->Status_Non_Active == 1) {
                // fall back to system
                $targetUserId = null;
            } else {
                $idMemberTarget = $targetUserId;
                $nameMemberTarget = $member ? $member->Name_Member : null;
            }
        }

        if (! $targetUserId) {
            $systemMember = Member::where('Name_Member', 'system')->first();
            $idMemberTarget = $systemMember ? $systemMember->Id_Member : 35;
            $nameMemberTarget = 'system';
        }

        return [$idMemberTarget, $nameMemberTarget];
    }

    /**
     * Display the unrecorded urgents view based on the current user role layout.
     */
    public function unrecordedIndex()
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

        return view('helpers.unrecorded_urgents', compact('layout'));
    }

    /**
     * Return datatables data for unrecorded urgents
     */
    public function getUnrecordedData(Request $request)
    {
        if ($request->ajax()) {
            $query = Urgent::with(['member', 'user', 'reporterMember', 'requestModel.rack', 'withdrawal.rack', 'mistake'])
                ->whereDoesntHave('record')
                ->orderBy('Time_Urgent', 'desc');

            // Custom Filter logic
            if ($codeRack = $request->input('codeRack')) {
                $query->where('Code_Rack', 'LIKE', '%'.$codeRack.'%');
            }

            if ($dateUrgent = $request->input('dateUrgent')) {
                $query->where('Time_Urgent', 'LIKE', '%'.$dateUrgent.'%');
            }

            if ($keyword = $request->input('keyword')) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('Code_Rack', 'LIKE', "%$keyword%")
                        ->orWhereHas('member', function ($q2) use ($keyword) {
                            $q2->where('Name_Member', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('mistake', function ($q2) use ($keyword) {
                            $q2->where('Category_Mistake', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('reporterMember', function ($q2) use ($keyword) {
                            $q2->where('Name_Member', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('user', function ($q2) use ($keyword) {
                            $q2->where('Username_User', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('requestModel.rack', function ($q2) use ($keyword) {
                            $q2->where('Name_Item_Rack', 'LIKE', "%$keyword%")
                                ->orWhere('Code_Item_Rack', 'LIKE', "%$keyword%");
                        })
                        ->orWhereHas('withdrawal.rack', function ($q2) use ($keyword) {
                            $q2->where('Name_Item_Rack', 'LIKE', "%$keyword%")
                                ->orWhere('Code_Item_Rack', 'LIKE', "%$keyword%");
                        });
                });
            }

            $urgents = $query->get();
            $grouped = $urgents->groupBy('Code_Rack')->map(function ($items) {
                $first = $items->first(); // Latest due to query order
                $first->Time_Urgent = $items->pluck('Time_Urgent')->unique()->map(function ($t) {
                    return \Carbon\Carbon::parse($t)->format('Y-m-d H:i:s');
                })->implode("\n");

                return $first;
            })->values();

            return DataTables::of($grouped)
                ->addColumn('PIC_Urgent', function ($urgent) {
                    // Check if this is a QC mistake
                    if ($urgent->mistake && strtolower($urgent->mistake->Category_Mistake) === 'telat qc') {
                        return 'QC';
                    }

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
                    } elseif ($cat == 'telat qc') {
                        $label = 'TELAT QC';
                        $class = 'pink';
                    } elseif ($cat == 'telat return rack') {
                        $label = 'TELAT RETURN RACK';
                        $class = 'secondary';
                    } elseif ($cat == 'stock') {
                        $label = 'STOCK';
                        $class = 'secondary';
                    } elseif ($cat == 'telat supply' || $cat == 'telat request') {
                        $class = 'secondary';
                    }

                    return '<span class="badge badge-'.$class.'">'.$label.'</span>';
                })
                ->addColumn('Name_Part', function ($urgent) {
                    if ($urgent->mistake && $urgent->mistake->Is_Withdrawal) {
                        return optional(optional($urgent->withdrawal)->rack)->Name_Item_Rack ?? '-';
                    }
                    return optional(optional($urgent->requestModel)->rack)->Name_Item_Rack ?? '-';
                })
                ->addColumn('Request_Details', function ($urgent) {
                    if ($urgent->mistake && $urgent->mistake->Is_Withdrawal) {
                        if ($urgent->withdrawal) {
                            return 'Item: '.$urgent->withdrawal->Code_Item_Withdrawal.' - (Withdrawal)';
                        }
                        return 'N/A';
                    }
                    if ($urgent->requestModel) {
                        return 'Item: '.$urgent->requestModel->Code_Item_Rack.' - Sum: '.$urgent->requestModel->Sum_Request;
                    }

                    return 'N/A';
                })
                ->addColumn('Reporter', function ($urgent) {
                    if ($urgent->Is_Marshalling) {
                        $employee = DB::connection('rifa')->table('employees')->find($urgent->Id_User);
                        $name = $employee ? $employee->nama : 'Marshalling User';
                        $seq = $urgent->Sequence_No_Record ? '<br>Sequence: '.e($urgent->Sequence_No_Record) : '';
                        return e($name).$seq;
                    }

                    if (empty($urgent->Id_Type_User)) {
                        return optional($urgent->reporterMember)->Name_Member ?? '-';
                    }

                    return optional($urgent->user)->Username_User ?? '-';
                })
                ->addColumn('Request_Time', function ($urgent) {
                    if ($urgent->mistake && $urgent->mistake->Is_Withdrawal) {
                        if ($urgent->withdrawal) {
                            return $urgent->withdrawal->Date_Withdrawal ? $urgent->withdrawal->Date_Withdrawal->format('Y-m-d H:i:s') : '-';
                        }
                        return '-';
                    }
                    if ($urgent->requestModel) {
                        return $urgent->requestModel->Day_Request.' '.$urgent->requestModel->Time_Request;
                    }

                    return '-';
                })
                ->editColumn('Time_Urgent', function ($urgent) {
                    return nl2br(e($urgent->Time_Urgent));
                })
                ->rawColumns(['Time_Urgent', 'PIC_Urgent', 'Name_Part', 'Mistake_Category', 'Request_Details', 'Reporter', 'Request_Time'])
                ->make(true);
        }

        return abort(403, 'Unauthorized action.');
    }

    /**
     * Export unrecorded urgents to excel.
     */
    public function exportUnrecorded(Request $request)
    {
        $date = Carbon::today()->format('Y-m-d');

        $query = Urgent::with(['member', 'user', 'reporterMember', 'requestModel.rack', 'withdrawal.rack', 'mistake'])
            ->whereDoesntHave('record')
            ->orderBy('Time_Urgent', 'desc');

        if ($codeRack = $request->input('codeRack')) {
            $query->where('Code_Rack', 'LIKE', '%'.$codeRack.'%');
        }

        if ($dateUrgent = $request->input('dateUrgent')) {
            $query->where('Time_Urgent', 'LIKE', '%'.$dateUrgent.'%');
            $date = $dateUrgent;
        }

        $urgents = $query->get();

        $groupedUrgents = $urgents->groupBy('Code_Rack')->map(function ($items) {
            $first = $items->first();
            $first->Time_Urgent = $items->pluck('Time_Urgent')->unique()->map(function ($t) {
                return \Carbon\Carbon::parse($t)->format('Y-m-d H:i:s');
            })->implode("\n");

            return $first;
        })->values();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['No', 'Time Urgent', 'Category', 'Code Rack', 'Name Part', 'PIC', 'Reporter', 'Code Item', 'Sum Request', 'Time Request'];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F4F4F']],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $row = 2;
        $no = 1;

        foreach ($groupedUrgents as $urgent) {
            $category = '-';
            if ($urgent->mistake) {
                $cat = strtolower($urgent->mistake->Category_Mistake);
                $detail = strtolower($urgent->mistake->Manual_Category_Detail);

                if ($cat == 'perubahan desain') {
                    $category = 'DESIGN CHANGE';
                } elseif ($cat == 'shipping') {
                    $category = 'SHIPPING';
                } elseif ($cat == 'lain-lain' && $detail == 'produksi') {
                    $category = 'PRODUCTION';
                } else {
                    $category = strtoupper($cat);
                }
            }

            $namePart = '-';
            if ($urgent->mistake && $urgent->mistake->Is_Withdrawal) {
                $namePart = optional(optional($urgent->withdrawal)->rack)->Name_Item_Rack ?? '-';
            } else {
                $namePart = optional(optional($urgent->requestModel)->rack)->Name_Item_Rack ?? '-';
            }

            $pic = $urgent->member ? $urgent->member->Name_Member : '-';
            if ($urgent->Is_Marshalling) {
                $employee = DB::connection('rifa')->table('employees')->find($urgent->Id_User);
                $name = $employee ? $employee->nama : 'Marshalling User';
                $reporter = $name;
                if ($urgent->Sequence_No_Record) {
                    $reporter .= ' (Sequence: '.$urgent->Sequence_No_Record.')';
                }
            } else {
                $reporter = empty($urgent->Id_Type_User)
                    ? (optional($urgent->reporterMember)->Name_Member ?? '-')
                    : (optional($urgent->user)->Username_User ?? '-');
            }

            $codeItem = '-';
            $sumReq = '-';
            $timeReq = '-';
            if ($urgent->mistake && $urgent->mistake->Is_Withdrawal) {
                if ($urgent->withdrawal) {
                    $codeItem = $urgent->withdrawal->Code_Item_Withdrawal;
                    $sumReq = '(Withdrawal)';
                    $timeReq = $urgent->withdrawal->Date_Withdrawal ? $urgent->withdrawal->Date_Withdrawal->format('Y-m-d H:i:s') : '-';
                }
            } else {
                if ($urgent->requestModel) {
                    $codeItem = $urgent->requestModel->Code_Item_Rack;
                    $sumReq = $urgent->requestModel->Sum_Request;
                    $timeReq = $urgent->requestModel->Day_Request.' '.$urgent->requestModel->Time_Request;
                }
            }

            $sheet->fromArray([
                $no,
                $urgent->Time_Urgent,
                $category,
                $urgent->Code_Rack,
                $namePart,
                $pic,
                $reporter,
                $codeItem,
                $sumReq,
                $timeReq,
            ], null, 'A'.$row);
            $sheet->getStyle('B'.$row)->getAlignment()->setWrapText(true);

            $no++;
            $row++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Urgent_Unrecorded_'.$date.'.xlsx';
        $filePath = storage_path('app/public/'.$fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
