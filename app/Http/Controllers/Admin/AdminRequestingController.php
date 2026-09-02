<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as RequestModel;

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

        $codeItem = substr($request->input('Code_Item'), 0, 12);

        // Cek apakah sudah ada request dengan status Waiting
        $existing = RequestModel::where('Code_Rack', $request->input('Code_Rack'))
            ->where('Code_Item_Rack', $codeItem)
            ->where('Status_Request', 'Waiting')
            ->where('Area_Request', $request->input('Area_Request'))
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Item ini sudah pernah direquest dan masih menunggu.');
        }

        // Tentukan waktu efektif: Urgent bypass cutoff, non-urgent pakai aturan 15:30
        $isUrgent = $request->has('Urgent_Request') && $request->input('Urgent_Request');
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
        $newRequest->Code_Rack = $request->input('Code_Rack');
        $newRequest->Id_User = $adminId;    // ID admin dari tabel users
        $newRequest->Is_User = 1;           // Flag: ini dari admin
        $newRequest->Status_Request = 'Waiting';
        $newRequest->Sum_Request = $request->input('Sum_Request');

        if ($request->filled('Correctness')) {
            $newRequest->Correctness_Request = $request->input('Correctness');
        }

        if ($request->input('Area_Request') !== '') {
            $newRequest->Area_Request = $request->input('Area_Request');
        }

        // tambahkan urgent_request
        $newRequest->Urgent_Request = $isUrgent ? 1 : 0;

        $newRequest->save();

        $filterParams = array_filter($request->only(['date', 'month', 'checker']));

        if ($request->has('check_id') && !empty($request->input('check_id'))) {
            $check = \App\Models\Check::find($request->input('check_id'));
            if ($check) {
                $check->Status_Check = null;
                $check->save();
            }
            if ($request->input('return_to_check') == 1) {
                return redirect()->route('admin.check', $filterParams)->with('success', 'Request berhasil dibuat dan Check ditandai Selesai.');
            }
        }

        // Flash message: beritahu admin jika request digeser
        if ($shifted) {
            $msg = 'Request melewati jam 15:30, dicatat sebagai request tanggal '
                . Carbon::parse($date)->translatedFormat('d F Y') . ' jam 07:45.';
        } else {
            $msg = 'Request berhasil dibuat.';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function check(Request $request)
    {
        $codeRack = $request->input('Code_Rack');
        $codeItem = substr($request->input('Code_Item'), 0, 10);

        $exists = RequestModel::where('Code_Rack', $codeRack)
            ->where('Code_Item_Request', 'LIKE', '%' . $codeItem . '%')
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
        $codeRack = $request->input('Code_Rack');

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
