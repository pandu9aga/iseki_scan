<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;
use Illuminate\Support\Facades\DB;

class AdminRequestingController extends Controller
{
    public function index(Request $request)
    {
        $area = $request->query('area');
        $check_id = $request->query('check_id');
        $code_rack = $request->query('code_rack');
        $code_item = $request->query('code_item');
        $filter_date = $request->query('date');
        $filter_month = $request->query('month');
        $filter_checker = $request->query('checker');

        return view('admins.requesting.index', compact('area', 'check_id', 'code_rack', 'code_item', 'filter_date', 'filter_month', 'filter_checker'));
    }

    public function create(Request $request)
    {
        $now = Carbon::now();

        // Ambil ID admin dari session (dari tabel users)
        $adminId = session('Id_User');

        // Validasi: ID admin harus ada, tidak boleh null
        if (!$adminId) {
            return redirect()->back()->with('error', 'Session admin tidak ditemukan. Silakan login ulang.');
        }

        $request->validate([
            'Code_Item' => 'required',
            'Code_Rack' => 'required',
            'Sum_Request' => 'required|integer|min:1',
            'Urgent_Request' => 'nullable|boolean',
        ], [
            'Code_Item.required' => 'Kode item wajib diisi',
            'Code_Rack.required' => 'Kode rack wajib diisi',
            'Sum_Request.required' => 'Jumlah permintaan wajib diisi',
            'Sum_Request.integer' => 'Jumlah permintaan harus berupa angka',
            'Sum_Request.min' => 'Jumlah permintaan minimal 1',
        ]);

        $codeRack = strtoupper(trim($request->input('Code_Rack')));
        $codeItem = substr($request->input('Code_Item'), 0, 12);
        $areaRequest = $request->input('Area_Request');
        $sumRequest = $request->input('Sum_Request');
        $isUrgent = $request->has('Urgent_Request') && $request->input('Urgent_Request');
        $correctness = $request->input('Correctness');
        $checkId = $request->input('check_id');
        $returnToCheck = $request->input('return_to_check');
        $filterParams = array_filter($request->only(['date', 'month', 'checker']));

        // Transaction & pessimistic locking untuk mencegah race condition / double submit
        try {
            $result = DB::transaction(function () use (
                $now, $adminId, $codeRack, $codeItem,
                $areaRequest, $sumRequest, $isUrgent, $correctness,
                $checkId, $returnToCheck, $filterParams
            ) {
                // Kunci pengecekan: hanya boleh 1 request aktif (Status_Request != 'Done') untuk nomor rak yang sama
                $existing = RequestModel::where('Code_Rack', $codeRack)
                    ->where('Status_Request', '!=', 'Done')
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return [
                        'status' => 'error',
                        'message' => 'Part dengan kode Rack ' . $codeRack . ' sedang dalam status ' . $existing->Status_Request . ' dan belum selesai (Done). Tidak dapat request ganda.'
                    ];
                }

                // Tentukan waktu efektif: Urgent bypass cutoff, non-urgent pakai aturan 15:30
                $shifted = false;
                if ($isUrgent) {
                    $date = $now->format('Y-m-d');
                    $timeNow = $now->format('H:i:s');
                } else {
                    $effective = \App\Models\SpecialDate::resolveEffectiveRequestTime($now);
                    $date = $effective['date'];
                    $timeNow = $effective['time'];
                    $shifted = $effective['shifted'];
                }

                $newRequest = new RequestModel();
                $newRequest->Day_Request = $date;
                $newRequest->Time_Request = $timeNow;
                $newRequest->Actual_Submitted_At = $now;
                $newRequest->Code_Item_Rack = $codeItem;
                $newRequest->Code_Rack = $codeRack;
                $newRequest->Id_User = $adminId;    // ID admin dari tabel users
                $newRequest->Is_User = 1;           // Flag: ini dari admin
                $newRequest->Status_Request = 'Waiting';
                $newRequest->Sum_Request = $sumRequest;

                if (!empty($correctness)) {
                    $newRequest->Correctness_Request = $correctness;
                }

                if ($areaRequest !== '') {
                    $newRequest->Area_Request = $areaRequest;
                }

                $newRequest->Urgent_Request = $isUrgent ? 1 : 0;
                $newRequest->save();

                if (!empty($checkId)) {
                    $check = \App\Models\Check::find($checkId);
                    if ($check) {
                        $check->Status_Check = null;
                        $check->save();
                    }
                }

                return [
                    'status' => 'success',
                    'shifted' => $shifted,
                    'date' => $date,
                    'return_to_check' => $returnToCheck,
                    'filter_params' => $filterParams,
                ];
            });

            if ($result['status'] === 'error') {
                return redirect()->back()->with('error', $result['message']);
            }

            if (!empty($result['return_to_check']) && $result['return_to_check'] == 1) {
                return redirect()->route('admin.check', $result['filter_params'])->with('success', 'Request berhasil dibuat dan Check ditandai Selesai.');
            }

            if ($result['shifted']) {
                $msg = 'Request melewati jam 15:30, dicatat sebagai request tanggal '
                    . Carbon::parse($result['date'])->translatedFormat('d F Y') . ' jam 07:00.';
            } else {
                $msg = 'Request berhasil dibuat.';
            }

            return redirect()->back()->with('success', $msg);

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses request: ' . $e->getMessage());
        }
    }

    public function check(Request $request)
    {
        $codeRack = $request->input('Code_Rack');
        $rawItem = (string) $request->input('Code_Item');
        $cleanItem = preg_replace('/[^\p{L}\p{N}]/u', '', $rawItem);
        $codeItem = substr($cleanItem, 0, 12);

        $exists = RequestModel::where('Code_Rack', $codeRack)
            ->where(function ($q) use ($cleanItem, $codeItem) {
                $q->where('Code_Item_Rack', $cleanItem)
                  ->orWhere('Code_Item_Rack', $codeItem)
                  ->orWhere('Code_Item_Rack', 'LIKE', '%' . $codeItem . '%');
            })
            ->exists();

        return response()->json([
            'status' => $exists ? 'correct' : 'incorrect'
        ]);
    }

    public function getData(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        $requests = RequestModel::whereDate('Day_Request', $date)
            ->orderBy('Time_Request', 'desc')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->Id_Request,
                    'code_item' => $r->Code_Item_Rack,
                    'code_rack' => $r->Code_Rack,
                    'sum_request' => $r->Sum_Request,
                    'area' => $r->Area_Request,
                    'status' => $r->Status_Request,
                    'time' => $r->Time_Request,
                    'user' => $r->display_name,
                ];
            });

        return response()->json([
            'date' => $date,
            'requests' => $requests,
            'count' => $requests->count(),
        ]);
    }

    public function checkDuplicate(Request $request)
    {
        $codeRack = strtoupper(trim($request->input('Code_Rack')));

        $existing = RequestModel::where('Code_Rack', $codeRack)
            ->where('Status_Request', '!=', 'Done')
            ->with('member')
            ->first();

        if ($existing) {
            // Jika Is_User = 1 → langsung tampilkan "Admin", tidak perlu lookup member
            $name = 'Unknown';
            if ($existing->Is_User == 1) {
                $name = 'Admin';
            } else {
                $name = optional($existing->member)->Name_Member ?? 'Unknown';
            }

            return response()->json([
                'exists' => true,
                'name' => $name,
                'day' => $existing->Day_Request,
                'time' => $existing->Time_Request,
            ]);
        }

        return response()->json(['exists' => false]);
    }
}
