<?php

namespace App\Http\Controllers\Area;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Mistake;
use App\Models\Rack;
use App\Models\Record;
use App\Models\Request as RequestModel;
use App\Models\Urgent;
use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;

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

        // Check if waiting request exists
        $waitingRequest = RequestModel::where('Code_Rack', $codeRack)
            ->where('Status_Request', 'Waiting')
            ->first();

        if ($waitingRequest) {
            // "Kalau Code_Rack dan status waiting ada, maka dapatkan Id_Request nya insert ke urgents"
            $idRequest = $waitingRequest->Id_Request;

            if ($waitingRequest->Ready_Request !== null) {
                // "jika Ready_Request not null, maka cari Id_Member rata-rata di records untuk Code_Rack yang sama"
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
                    $nameMemberTarget = $member ? $member->Name_Member : null;
                } else {
                    $systemMember = Member::where('Name_Member', 'system')->first();
                    $idMemberTarget = $systemMember ? $systemMember->Id_Member : 34;
                    $nameMemberTarget = 'system';
                }

                // "insert di urgents Id_Member itu juga"
                Urgent::create([
                    'Id_User' => $idUserLogged,
                    'Code_Rack' => $codeRack,
                    'Id_Request' => $idRequest,
                    'Id_Member' => $idMemberTarget,
                    'Time_Urgent' => $nowTime,
                ]);

                // "insert di mistakes category telat supply dengan Id_Member tersebut"
                Mistake::create([
                    'Id_Request' => $idRequest,
                    'PIC' => $nameMemberTarget,
                    'Category_Mistake' => 'telat supply',
                    'Day_Mistake' => $nowDate,
                ]);

            } else {
                // "sedangkan jika status ready null maka insert ke urgents dan mistakes nya menggunakan Id_member dengan nama Boss MC"
                $bossMcMember = Member::where('Name_Member', 'Boss MC')->first();
                $idBossMc = $bossMcMember ? $bossMcMember->Id_Member : 32;
                $nameBossMc = 'Boss MC';

                // "category mistakes nya kalau ready, shipping, design, dan production null semua maka telat supply mc"
                // "kalau shipping nya ada maka category nya shipping"
                // "kalau design ada maka category nya design"
                // "kalau production maka category nya production"
                $category = 'telat supply mc';
                if ($waitingRequest->Production_Area_Request !== null) {
                    $category = 'production';
                } elseif ($waitingRequest->Design_Changes_Request !== null) {
                    $category = 'design';
                } elseif ($waitingRequest->Shipping_Request !== null) {
                    $category = 'shipping';
                }

                Urgent::create([
                    'Id_User' => $idUserLogged,
                    'Code_Rack' => $codeRack,
                    'Id_Request' => $idRequest,
                    'Id_Member' => $idBossMc,
                    'Time_Urgent' => $nowTime,
                ]);

                Mistake::create([
                    'Id_Request' => $idRequest,
                    'PIC' => $nameBossMc,
                    'Category_Mistake' => $category,
                    'Day_Mistake' => $nowDate,
                ]);
            }

        } else {
            // "kalau tidak ada maka cari Id_Member rata-rata yang melakukan request code tersebut"
            $avgMemberReq = RequestModel::select('Id_User', DB::raw('COUNT(Id_User) as count'))
                ->where('Code_Rack', $codeRack)
                ->groupBy('Id_User')
                ->orderBy('count', 'desc')
                ->first();

            $idMemberTarget = null;
            $nameMemberTarget = null;
            if ($avgMemberReq) {
                // Id_User in Request table is actually Id_Member mapped from Member table
                $idMemberTarget = $avgMemberReq->Id_User;
                $member = Member::find($idMemberTarget);
                $nameMemberTarget = $member ? $member->Name_Member : null;
            } else {
                // "kalau tidak ada maka Cari Id_Member dengan nama system"
                $systemMember = Member::where('Name_Member', 'system')->first();
                $idMemberTarget = $systemMember ? $systemMember->Id_Member : 34;
                $nameMemberTarget = 'system';
            }

            // "untuk sum request nya isi berdasarkan sum request terakhir dari kode rak yang sama, kalau tidak ada maka default isi 1"
            $lastReq = RequestModel::where('Code_Rack', $codeRack)->orderBy('Id_Request', 'desc')->first();
            $sumRequest = 1;
            if ($lastReq && $lastReq->Sum_Request) {
                $sumRequest = $lastReq->Sum_Request;
            }

            // Get Code_Item_Rack for the new request
            $rack = Rack::where('Code_Rack', $codeRack)->first();
            $codeItemRack = $rack ? $rack->Code_Item_Rack : ($lastReq ? $lastReq->Code_Item_Rack : null);

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

            Urgent::create([
                'Id_User' => $idUserLogged,
                'Code_Rack' => $codeRack,
                'Id_Request' => $idRequestNew,
                'Id_Member' => $idMemberTarget,
                'Time_Urgent' => $nowTime,
            ]);

            Mistake::create([
                'Id_Request' => $idRequestNew,
                'PIC' => $nameMemberTarget,
                'Category_Mistake' => 'telat request',
                'Day_Mistake' => $nowDate,
            ]);
        }

        return redirect()->back()->with('success', 'Scan Code Rack '.$codeRack.' berhasil diproses.');
    }
}
