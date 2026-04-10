<?php

namespace App\Http\Controllers\Area;

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
use Illuminate\Http\Request as HttpRequest;

class AreaScanController extends Controller
{
    public function index()
    {
        return view('areas.scan');
    }

    public function process(HttpRequest $request)
    {
        $codeRack = $request->input('Code_Rack');
        $idUserLogged = session('Id_User'); // ID_User of type 4 logged in
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

        // Check if waiting request exists
        $waitingRequest = RequestModel::where('Code_Rack', $codeRack)
            ->where('Status_Request', 'Waiting')
            ->first();

        if ($waitingRequest) {
            $rackModel = Rack::where('Code_Rack', $codeRack)->first();
            $namePart = $rackModel ? ($rackModel->Name_Item_Rack ?? '-') : '-';
            $idRequest = $waitingRequest->Id_Request;

            // Check if request is less than 24 hours old
            $requestTime = Carbon::parse($waitingRequest->Day_Request.' '.$waitingRequest->Time_Request);
            $isLessThan24Hours = $requestTime->diffInHours(Carbon::now()) < 24;

            if ($isLessThan24Hours) {
                // If < 24 hours, category is "telat request" and PIC is the member responsible
                [$idMemberTarget, $nameMemberTarget] = $this->getLastRequestPic($codeRack);

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
                    'Id_User' => $idUserLogged,
                    'Id_Type_User' => session('Id_Type_User'),
                    'Code_Rack' => $codeRack,
                    'Id_Request' => $idRequest,
                    'Id_Member' => $idMemberTarget,
                    'Time_Urgent' => $nowTime,
                    'Id_Mistake' => $mistake->Id_Mistake,
                ]);

                // Queue WA notification
                $reporter = User::find($idUserLogged);
                $this->queueWaMessage([
                    'time_urgent' => $nowTime,
                    'code_rack' => $codeRack,
                    'pic' => $nameMemberTarget,
                    'reporter' => $reporter ? $reporter->Name_User : 'Area User',
                    'code_item' => $waitingRequest->Code_Item_Rack,
                    'name_part' => $namePart,
                    'sum_request' => $waitingRequest->Sum_Request,
                    'category' => 'telat request',
                    'time_request' => $waitingRequest->Day_Request.' '.$waitingRequest->Time_Request,
                ]);
            } elseif ($waitingRequest->Ready_Request !== null) {
                // "jika Ready_Request not null, maka cari Id_Member rata-rata di records untuk Code_Rack yang sama"
                [$idMemberTarget, $nameMemberTarget] = $this->getLastRecordPic($codeRack);

                $category = 'telat supply';
                $manualDetail = null;

                // "insert di mistakes category telat supply dengan Id_Member tersebut"
                $mistake = Mistake::create([
                    'Id_Request' => $idRequest,
                    'PIC' => $nameMemberTarget,
                    'Category_Mistake' => $category,
                    'Day_Mistake' => $nowDate,
                    'Status_Mistake' => 0,
                ]);

                // "insert di urgents Id_Member itu juga"
                $urgent = Urgent::create([
                    'Id_User' => $idUserLogged,
                    'Id_Type_User' => session('Id_Type_User'),
                    'Code_Rack' => $codeRack,
                    'Id_Request' => $idRequest,
                    'Id_Member' => $idMemberTarget,
                    'Time_Urgent' => $nowTime,
                    'Id_Mistake' => $mistake->Id_Mistake,
                ]);

                // Queue WA notification
                $reporter = User::find($idUserLogged);
                $this->queueWaMessage([
                    'time_urgent' => $nowTime,
                    'code_rack' => $codeRack,
                    'pic' => $nameMemberTarget,
                    'reporter' => $reporter ? $reporter->Name_User : 'Area User',
                    'code_item' => $waitingRequest->Code_Item_Rack,
                    'name_part' => $namePart,
                    'sum_request' => $waitingRequest->Sum_Request,
                    'category' => 'telat supply',
                    'time_request' => $waitingRequest->Day_Request.' '.$waitingRequest->Time_Request,
                ]);

            } else {
                // "sedangkan jika status ready null maka insert ke urgents dan mistakes nya menggunakan Id_member dengan nama Boss MC"
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
                    'Id_User' => $idUserLogged,
                    'Id_Type_User' => session('Id_Type_User'),
                    'Code_Rack' => $codeRack,
                    'Id_Request' => $idRequest,
                    'Id_Member' => $idBossMc,
                    'Time_Urgent' => $nowTime,
                    'Id_Mistake' => $mistake->Id_Mistake,
                ]);

                // Queue WA notification
                $reporter = User::find($idUserLogged);
                $this->queueWaMessage([
                    'time_urgent' => $nowTime,
                    'code_rack' => $codeRack,
                    'pic' => $nameBossMc,
                    'reporter' => $reporter ? $reporter->Name_User : 'Area User',
                    'code_item' => $waitingRequest->Code_Item_Rack,
                    'name_part' => $namePart,
                    'sum_request' => $waitingRequest->Sum_Request,
                    'category' => $category,
                    'time_request' => $waitingRequest->Day_Request.' '.$waitingRequest->Time_Request,
                ]);
            }

            // Capture data for success modal
            $pic = ($isLessThan24Hours || $waitingRequest->Ready_Request !== null) ? $nameMemberTarget : $nameBossMc;

            $cat = strtolower($category ?? 'telat supply'); // category is set in the logic above
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
                'success' => 'Scan Code Rack '.$codeRack.' berhasil diproses.',
                'scan_success_data' => $scanSuccessData,
            ]);

        } else {
            // "kalau tidak ada maka cari Id_Member rata-rata yang melakukan request code tersebut"
            // Diganti menggunakan last PIC berdasar logic baru
            [$idMemberTarget, $nameMemberTarget] = $this->getLastRequestPic($codeRack);

            // "untuk sum request nya isi berdasarkan sum request terakhir dari kode rak yang sama, kalau tidak ada maka default isi 1"
            $lastReq = RequestModel::where('Code_Rack', $codeRack)->orderBy('Id_Request', 'desc')->first();
            $sumRequest = 1;
            if ($lastReq && $lastReq->Sum_Request) {
                $sumRequest = $lastReq->Sum_Request;
            }

            // Get Code_Item_Rack for the new request
            $rack = Rack::where('Code_Rack', $codeRack)->first();
            $codeItemRack = $rack ? $rack->Code_Item_Rack : ($lastReq ? $lastReq->Code_Item_Rack : null);

            if (! $codeItemRack) {
                return redirect()->back()->with('error', 'Kode Rack "'.$codeRack.'" tidak ditemukan di Data Rack. Silakan cek kembali atau hubungi Admin.');
            }

            // "lalu insert ke tabel urgents, requests dan mistakes, PIC = Name_Member, Category telat request"
            // request first to get Id_Request
            $newReq = new RequestModel;
            $newReq->Day_Request = $nowDate;
            $newReq->Time_Request = $nowTime;
            $newReq->Code_Item_Rack = $codeItemRack;
            $newReq->Code_Rack = $codeRack;
            $newReq->Id_User = $idMemberTarget; // Member ID who made requests
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
                'Id_User' => $idUserLogged,
                'Id_Type_User' => session('Id_Type_User'),
                'Code_Rack' => $codeRack,
                'Id_Request' => $idRequestNew,
                'Id_Member' => $idMemberTarget,
                'Time_Urgent' => $nowTime,
                'Id_Mistake' => $mistake->Id_Mistake,
            ]);

            // Queue WA notification
            $reporter = User::find($idUserLogged);
            $namePart = $rack ? ($rack->Name_Item_Rack ?? '-') : '-';
            $this->queueWaMessage([
                'time_urgent' => $nowTime,
                'code_rack' => $codeRack,
                'pic' => $nameMemberTarget,
                'reporter' => $reporter ? $reporter->Name_User : 'Area User',
                'code_item' => $codeItemRack,
                'name_part' => $namePart,
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
                'success' => 'Scan Code Rack '.$codeRack.' berhasil diproses.',
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
}
