<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use App\Models\BranchRecord;
use App\Models\Check;
use App\Models\Member;
use App\Models\Record;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Models\WaQueue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WaQueueController extends Controller
{
    const WA_GROUP_ID = '120363045467407165@g.us';

    const WA_ACHIEVEMENT_GROUP_ID = '120363026880582483@g.us';

    const WA_TOKEN = 'NOFl7qr6DjYqG4jiy3MOmecZrzPfqkCeLQh76lpawgIRAi6ZSKfPXOB';

    const WA_HOST = 'https://solo.wablas.com/';

    /**
     * Halaman monitoring antrian WA
     */
    public function index()
    {
        $this->autoQueueDailyAchievement();

        $queues = WaQueue::where('status', 'pending')->orderBy('created_at', 'asc')->get();

        $today = Carbon::today();
        $todayHeader = 'Perolehan DST ' . $today->locale('id')->isoFormat('D MMMM Y');
        $achievementQueuedToday = WaQueue::where('group_id', self::WA_ACHIEVEMENT_GROUP_ID)
            ->whereDate('created_at', $today)
            ->where('message', 'like', $todayHeader . '%')
            ->exists();

        $achievementCutoffLabel = $this->getAchievementCutoffLabel($today);
        $achievementCutoffTime = $this->getAchievementCutoffTime($today);
        $isFriday = $today->isFriday();

        return view('admins.wa_queue', compact(
            'queues',
            'achievementQueuedToday',
            'achievementCutoffLabel',
            'achievementCutoffTime',
            'isFriday'
        ));
    }

    /**
     * API: ambil semua pending messages dalam format JSON
     */
    public function fetch()
    {
        $this->autoQueueDailyAchievement();

        $queues = WaQueue::where('status', 'pending')->orderBy('created_at', 'asc')->get();

        return response()->json($queues);
    }

    /**
     * Waktu cutoff perolehan achievement:
     * - Hari Jumat: 16:50:00 (16.50 WIB)
     * - Hari selain Jumat: 16:20:00 (16.20 WIB)
     */
    public function getAchievementCutoffTime(Carbon $date): string
    {
        return $date->isFriday() ? '16:50:00' : '16:20:00';
    }

    public function getAchievementCutoffLabel(Carbon $date): string
    {
        return $date->isFriday() ? '16.50 WIB' : '16.20 WIB';
    }

    /**
     * Otomatis mengantrekan pesan perolehan hari ini jika jam sudah >= cutoff
     * (Jumat: 16:50, hari lain: 16:20) dan belum pernah terinsert untuk hari ini.
     */
    public function autoQueueDailyAchievement(): ?WaQueue
    {
        $now = Carbon::now();
        $cutoff = $this->getAchievementCutoffTime($now);

        if ($now->format('H:i:s') < $cutoff) {
            return null;
        }

        return $this->insertAchievementQueueForDate($now);
    }

    /**
     * Insert achievement queue for a given date with duplicate check.
     */
    public function insertAchievementQueueForDate(Carbon $date): ?WaQueue
    {
        $dateStr = $date->format('Y-m-d');
        $headerDate = $date->locale('id')->isoFormat('D MMMM Y');
        $todayHeader = 'Perolehan DST ' . $headerDate;

        // Atomic lock to prevent race conditions across multiple open tabs/requests
        return Cache::lock('wa_achievement_queue_lock_' . $dateStr, 15)->get(function () use ($date, $todayHeader) {
            $alreadyExists = WaQueue::where('group_id', self::WA_ACHIEVEMENT_GROUP_ID)
                ->whereDate('created_at', $date->toDateString())
                ->where('message', 'like', $todayHeader . '%')
                ->exists();

            if ($alreadyExists) {
                return null;
            }

            $message = $this->buildAchievementMessage($date);

            return WaQueue::create([
                'group_id' => self::WA_ACHIEVEMENT_GROUP_ID,
                'message'  => $message,
                'status'   => 'pending',
            ]);
        });
    }

    /**
     * Manual trigger / preview route untuk generate achievement WA
     */
    public function triggerAchievement(Request $request)
    {
        $dateInput = $request->input('date', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($dateInput);

        $headerDate = $date->locale('id')->isoFormat('D MMMM Y');
        $todayHeader = 'Perolehan DST ' . $headerDate;

        $alreadyExists = WaQueue::where('group_id', self::WA_ACHIEVEMENT_GROUP_ID)
            ->whereDate('created_at', $date->toDateString())
            ->where('message', 'like', $todayHeader . '%')
            ->exists();

        if ($alreadyExists && !$request->boolean('force')) {
            return response()->json([
                'success' => false,
                'message' => "Pesan perolehan untuk tanggal {$headerDate} sudah pernah dibuat hari ini.",
                'already_exists' => true,
            ]);
        }

        $message = $this->buildAchievementMessage($date);

        $queue = WaQueue::create([
            'group_id' => self::WA_ACHIEVEMENT_GROUP_ID,
            'message'  => $message,
            'status'   => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pesan perolehan untuk {$headerDate} berhasil ditambahkan ke antrean WA!",
            'queue'   => $queue,
        ]);
    }

    /**
     * Preview pesan achievement perolehan hari tersebut sampai jam 16:20
     */
    public function previewAchievement(Request $request)
    {
        $dateInput = $request->input('date', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($dateInput);
        $message = $this->buildAchievementMessage($date);

        $headerDate = $date->locale('id')->isoFormat('D MMMM Y');
        $todayHeader = 'Perolehan DST ' . $headerDate;
        $alreadyExists = WaQueue::where('group_id', self::WA_ACHIEVEMENT_GROUP_ID)
            ->whereDate('created_at', $date->toDateString())
            ->where('message', 'like', $todayHeader . '%')
            ->exists();

        return response()->json([
            'date'           => $dateInput,
            'message'        => $message,
            'already_exists' => $alreadyExists,
        ]);
    }

    /**
     * Menyusun teks pesan perolehan DST sampai waktu cutoff:
     * - Jumat: 16.50 WIB
     * - Hari lain: 16.20 WIB
     */
    public function buildAchievementMessage(Carbon $date): string
    {
        $targetDate = $date->format('Y-m-d');
        $formattedDate = $date->locale('id')->isoFormat('D MMMM Y');
        $cutoffTime = $this->getAchievementCutoffTime($date);
        $cutoffLabel = $this->getAchievementCutoffLabel($date);
        $people = $this->getPeopleMap();

        // ── 1. Checks (Time_Check <= Cutoff) ─────────────────────────
        $checks = Check::whereDate('Time_Check', $targetDate)
            ->whereTime('Time_Check', '<=', $cutoffTime)
            ->where(function ($q) {
                $q->whereNotNull('Status_Check')
                  ->orWhere('Auto_Check', 1);
            })
            ->get(['Id_User', 'Is_User']);

        $checkCounts = [];
        foreach ($checks as $c) {
            $prefix = ($c->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix . $c->Id_User;
            $checkCounts[$key] = ($checkCounts[$key] ?? 0) + 1;
        }

        $checkList = [];
        foreach ($checkCounts as $key => $count) {
            if ($count > 0) {
                $name = $people[$key] ?? 'Unknown';
                $checkList[] = ['name' => $name, 'count' => $count];
            }
        }
        usort($checkList, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        $totalCheck = $checks->count();

        // ── 2. Requests (Time_Request <= Cutoff) ──────────────────────
        $requests = RequestModel::whereDate('Day_Request', $targetDate)
            ->whereTime('Time_Request', '<=', $cutoffTime)
            ->get(['Id_User', 'Is_User']);

        $requestCounts = [];
        foreach ($requests as $r) {
            $prefix = ($r->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix . $r->Id_User;
            $requestCounts[$key] = ($requestCounts[$key] ?? 0) + 1;
        }

        $requestList = [];
        foreach ($requestCounts as $key => $count) {
            if ($count > 0) {
                $name = $people[$key] ?? 'Unknown';
                $requestList[] = ['name' => $name, 'count' => $count];
            }
        }
        usort($requestList, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        $totalRequest = $requests->count();

        // ── 3. Records: Biasa + Branch (Time <= Cutoff) ───────────────
        $records = Record::whereDate('Day_Record', $targetDate)
            ->whereTime('Time_Record', '<=', $cutoffTime)
            ->get(['Id_User', 'Is_User']);

        $branchRecords = BranchRecord::whereDate('Day_Branch_Record', $targetDate)
            ->whereTime('Time_Branch_Record', '<=', $cutoffTime)
            ->get(['Id_User']);

        $recordCounts = [];
        foreach ($records as $rec) {
            $prefix = ($rec->Is_User == 1) ? 'u_' : 'm_';
            $key = $prefix . $rec->Id_User;
            $recordCounts[$key] = ($recordCounts[$key] ?? 0) + 1;
        }

        foreach ($branchRecords as $br) {
            $key = 'm_' . $br->Id_User;
            $recordCounts[$key] = ($recordCounts[$key] ?? 0) + 1;
        }

        $recordList = [];
        foreach ($recordCounts as $key => $count) {
            if ($count > 0) {
                $name = $people[$key] ?? 'Unknown';
                $recordList[] = ['name' => $name, 'count' => $count];
            }
        }
        usort($recordList, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        $totalRecord = $records->count() + $branchRecords->count();

        // ── Format Teks Pesan ───────────────────────────────────────────
        $divider = '---------------------------------------------------';
        $lines = [];
        $lines[] = "Perolehan DST {$formattedDate}, {$cutoffLabel}";
        $lines[] = $divider;

        // Bagian Check
        $lines[] = "Total Check ({$totalCheck})";
        if (count($checkList) > 0) {
            foreach ($checkList as $idx => $item) {
                $no = $idx + 1;
                $lines[] = "{$no}. {$item['name']} - {$item['count']}";
            }
        } else {
            $lines[] = "-";
        }
        $lines[] = $divider;

        // Bagian Request
        $lines[] = "Total Request ({$totalRequest})";
        if (count($requestList) > 0) {
            foreach ($requestList as $idx => $item) {
                $no = $idx + 1;
                $lines[] = "{$no}. {$item['name']} - {$item['count']}";
            }
        } else {
            $lines[] = "-";
        }
        $lines[] = $divider;

        // Bagian Record
        $lines[] = "Total Record ({$totalRecord})";
        if (count($recordList) > 0) {
            foreach ($recordList as $idx => $item) {
                $no = $idx + 1;
                $lines[] = "{$no}. {$item['name']} - {$item['count']}";
            }
        } else {
            $lines[] = "-";
        }
        $lines[] = '---------------------------------------------------';
        $lines[] = 'おつかれさまでした。。。⸜(｡˃ ᵕ ˂ )⸝♡';

        return implode("\n", $lines);
    }

    /**
     * Map member & user id ke nama display
     */
    private function getPeopleMap(): array
    {
        $map = [];

        $members = Member::all(['Id_Member', 'Name_Member']);
        foreach ($members as $m) {
            $map['m_' . $m->Id_Member] = $m->Name_Member;
        }

        $users = User::all(['Id_User', 'Name_User']);
        foreach ($users as $u) {
            $map['u_' . $u->Id_User] = $u->Name_User;
        }

        return $map;
    }

    /**
     * API: tandai pesan sebagai terkirim
     */
    public function markSent($id)
    {
        $queue = WaQueue::findOrFail($id);
        $queue->update(['status' => 'sent']);

        return response()->json(['success' => true, 'message' => 'Pesan ditandai terkirim.']);
    }

    /**
     * API: hapus pesan (ubah status ke cancelled)
     */
    public function destroy($id)
    {
        $queue = WaQueue::findOrFail($id);
        $queue->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Pesan dibatalkan.']);
    }

    /**
     * API: tandai pesan sebagai gagal
     */
    public function markFailed($id)
    {
        $queue = WaQueue::findOrFail($id);
        $queue->update(['status' => 'failed']);

        return response()->json(['success' => true, 'message' => 'Pesan ditandai gagal.']);
    }
}
