<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use App\Models\BranchRecord;
use App\Models\Check;
use App\Models\Member;
use App\Models\Mistake;
use App\Models\Record;
use App\Models\Request as RequestModel;
use App\Models\SpecialDate;
use App\Models\User;
use App\Models\WaQueue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaQueueController extends Controller
{
    const WA_GROUP_ID = '120363045467407165@g.us';

    const WA_ACHIEVEMENT_GROUP_ID = '120363026880582483@g.us';

    const WA_OPERATIONAL_SUMMARY_GROUP_ID = '120363160707493007@g.us';

    const WA_TOKEN = 'NOFl7qr6DjYqG4jiy3MOmecZrzPfqkCeLQh76lpawgIRAi6ZSKfPXOB';

    const WA_HOST = 'https://solo.wablas.com/';

    /**
     * Halaman monitoring antrian WA
     */
    public function index()
    {
        $this->autoQueueDailyAchievement();
        $this->autoQueueDailyOperationalSummary();

        $queues = WaQueue::where('status', 'pending')->orderBy('created_at', 'asc')->get();

        $today = Carbon::today();
        $todayHeader = 'Perolehan DST '.$today->locale('id')->isoFormat('D MMMM Y');
        $achievementQueuedToday = WaQueue::where('group_id', self::WA_ACHIEVEMENT_GROUP_ID)
            ->whereDate('created_at', $today)
            ->where('message', 'like', $todayHeader.'%')
            ->exists();

        $summaryHeader = '⛔ Bad News '.$today->locale('id')->isoFormat('D MMMM Y');
        $summaryQueuedToday = WaQueue::where('group_id', self::WA_OPERATIONAL_SUMMARY_GROUP_ID)
            ->whereDate('created_at', $today)
            ->where(function ($q) use ($summaryHeader, $today) {
                $q->where('message', 'like', $summaryHeader.'%')
                    ->orWhere('message', 'like', 'PDX Report '.$today->locale('id')->isoFormat('D MMMM Y').'%')
                    ->orWhere('message', 'like', 'Rangkuman Operasional '.$today->locale('id')->isoFormat('D MMMM Y').'%');
            })
            ->exists();

        $achievementCutoffLabel = $this->getAchievementCutoffLabel($today);
        $achievementCutoffTime = $this->getAchievementCutoffTime($today);
        $isFriday = $today->isFriday();

        return view('admins.wa_queue', compact(
            'queues',
            'achievementQueuedToday',
            'summaryQueuedToday',
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
        $this->autoQueueDailyOperationalSummary();

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
        $todayHeader = 'Perolehan DST '.$headerDate;

        // Atomic lock to prevent race conditions across multiple open tabs/requests
        return Cache::lock('wa_achievement_queue_lock_'.$dateStr, 15)->get(function () use ($date, $todayHeader) {
            $alreadyExists = WaQueue::where('group_id', self::WA_ACHIEVEMENT_GROUP_ID)
                ->whereDate('created_at', $date->toDateString())
                ->where('message', 'like', $todayHeader.'%')
                ->exists();

            if ($alreadyExists) {
                return null;
            }

            $message = $this->buildAchievementMessage($date);

            return WaQueue::create([
                'group_id' => self::WA_ACHIEVEMENT_GROUP_ID,
                'message' => $message,
                'status' => 'pending',
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
        $todayHeader = 'Perolehan DST '.$headerDate;

        $alreadyExists = WaQueue::where('group_id', self::WA_ACHIEVEMENT_GROUP_ID)
            ->whereDate('created_at', $date->toDateString())
            ->where('message', 'like', $todayHeader.'%')
            ->exists();

        if ($alreadyExists && ! $request->boolean('force')) {
            return response()->json([
                'success' => false,
                'message' => "Pesan perolehan untuk tanggal {$headerDate} sudah pernah dibuat hari ini.",
                'already_exists' => true,
            ]);
        }

        $message = $this->buildAchievementMessage($date);

        $queue = WaQueue::create([
            'group_id' => self::WA_ACHIEVEMENT_GROUP_ID,
            'message' => $message,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pesan perolehan untuk {$headerDate} berhasil ditambahkan ke antrean WA!",
            'queue' => $queue,
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
        $todayHeader = 'Perolehan DST '.$headerDate;
        $alreadyExists = WaQueue::where('group_id', self::WA_ACHIEVEMENT_GROUP_ID)
            ->whereDate('created_at', $date->toDateString())
            ->where('message', 'like', $todayHeader.'%')
            ->exists();

        return response()->json([
            'date' => $dateInput,
            'message' => $message,
            'already_exists' => $alreadyExists,
        ]);
    }

    /**
     * Otomatis mengantrekan pesan rangkuman operasional harian jika jam sudah >= cutoff
     * dan belum pernah terinsert untuk hari ini.
     */
    public function autoQueueDailyOperationalSummary(): ?WaQueue
    {
        $now = Carbon::now();
        $cutoff = $this->getAchievementCutoffTime($now);

        if ($now->format('H:i:s') < $cutoff) {
            return null;
        }

        return $this->insertOperationalSummaryQueueForDate($now);
    }

    /**
     * Insert operational summary queue for a given date with duplicate check.
     * Menggunakan DB transaction + lockForUpdate() agar atomic di semua cache driver.
     */
    public function insertOperationalSummaryQueueForDate(Carbon $date): ?WaQueue
    {
        $headerDate = $date->locale('id')->isoFormat('D MMMM Y');
        $todayHeader = '⛔ Bad News '.$headerDate;
        $legacyHeader1 = 'PDX Report '.$headerDate;
        $legacyHeader2 = 'Rangkuman Operasional '.$headerDate;

        return DB::transaction(function () use ($date, $todayHeader, $legacyHeader1, $legacyHeader2) {
            // lockForUpdate() mencegah race condition: row tidak bisa dibaca
            // oleh transaksi lain sampai transaksi ini selesai.
            $alreadyExists = WaQueue::where('group_id', self::WA_OPERATIONAL_SUMMARY_GROUP_ID)
                ->whereDate('created_at', $date->toDateString())
                ->where(function ($q) use ($todayHeader, $legacyHeader1, $legacyHeader2) {
                    $q->where('message', 'like', $todayHeader.'%')
                        ->orWhere('message', 'like', $legacyHeader1.'%')
                        ->orWhere('message', 'like', $legacyHeader2.'%');
                })
                ->lockForUpdate()
                ->exists();

            if ($alreadyExists) {
                return null;
            }

            $message = $this->buildOperationalSummaryMessage($date);

            return WaQueue::create([
                'group_id' => self::WA_OPERATIONAL_SUMMARY_GROUP_ID,
                'message' => $message,
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Manual trigger route untuk generate rangkuman operasional WA
     */
    public function triggerOperationalSummary(Request $request)
    {
        $dateInput = $request->input('date', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($dateInput);

        $headerDate = $date->locale('id')->isoFormat('D MMMM Y');
        $todayHeader = '⛔ Bad News '.$headerDate;
        $legacyHeader1 = 'PDX Report '.$headerDate;
        $legacyHeader2 = 'Rangkuman Operasional '.$headerDate;

        $alreadyExists = WaQueue::where('group_id', self::WA_OPERATIONAL_SUMMARY_GROUP_ID)
            ->whereDate('created_at', $date->toDateString())
            ->where(function ($q) use ($todayHeader, $legacyHeader1, $legacyHeader2) {
                $q->where('message', 'like', $todayHeader.'%')
                    ->orWhere('message', 'like', $legacyHeader1.'%')
                    ->orWhere('message', 'like', $legacyHeader2.'%');
            })
            ->exists();

        if ($alreadyExists && ! $request->boolean('force')) {
            return response()->json([
                'success' => false,
                'message' => "Pesan Bad News untuk tanggal {$headerDate} sudah pernah dibuat hari ini.",
                'already_exists' => true,
            ]);
        }

        $message = $this->buildOperationalSummaryMessage($date);

        $queue = WaQueue::create([
            'group_id' => self::WA_OPERATIONAL_SUMMARY_GROUP_ID,
            'message' => $message,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pesan Bad News untuk {$headerDate} berhasil ditambahkan ke antrean WA!",
            'queue' => $queue,
        ]);
    }

    /**
     * Preview pesan rangkuman operasional
     */
    public function previewOperationalSummary(Request $request)
    {
        $dateInput = $request->input('date', Carbon::today()->format('Y-m-d'));
        $date = Carbon::parse($dateInput);
        $message = $this->buildOperationalSummaryMessage($date);

        $headerDate = $date->locale('id')->isoFormat('D MMMM Y');
        $todayHeader = '⛔ Bad News '.$headerDate;
        $legacyHeader1 = 'PDX Report '.$headerDate;
        $legacyHeader2 = 'Rangkuman Operasional '.$headerDate;
        $alreadyExists = WaQueue::where('group_id', self::WA_OPERATIONAL_SUMMARY_GROUP_ID)
            ->whereDate('created_at', $date->toDateString())
            ->where(function ($q) use ($todayHeader, $legacyHeader1, $legacyHeader2) {
                $q->where('message', 'like', $todayHeader.'%')
                    ->orWhere('message', 'like', $legacyHeader1.'%')
                    ->orWhere('message', 'like', $legacyHeader2.'%');
            })
            ->exists();

        return response()->json([
            'date' => $dateInput,
            'message' => $message,
            'already_exists' => $alreadyExists,
        ]);
    }

    /**
     * Menyusun teks pesan rangkuman operasional terpadu
     */
    public function buildOperationalSummaryMessage(Carbon $date): string
    {
        $formattedDate = $date->locale('id')->isoFormat('D MMMM Y');
        $cutoffLabel = $this->getAchievementCutoffLabel($date);
        $divider = '---------------------------------------------------';

        // 1. Digital Pokayoke
        $pokayokeNg = $this->getPokayokeNgProcessesCount($date);

        // 2. Iseki Scan (Urgent & Missing)
        $scanData = $this->getScanOperationalData($date);

        // 2b. Marshalling Part Kurang
        $marshallingPartKurang = $this->getMarshallingPartKurangSummary($date);

        // 3. Aspro
        $aspro = $this->getAsproOperationalData($date);

        // 4. Rifa (Absen Sakit)
        $rifaSakit = $this->getRifaSickLeaveCount($date);

        // 5 & 6. Parcom & Chadet
        $ngData = $this->getParcomAndChadetNgCount($date);

        // 7. Devmon
        $devmonUnsubmitted = $this->getDevmonUnsubmittedDevices($date);

        // 8. KYT (Minggu Lalu)
        $kyt = $this->getKytLastWeekUnsubmittedPerArea($date);

        // 9. Podium (S Minus)
        $podiumMinus = $this->getPodiumMinusSummary($date);

        // 10. Efficiency
        $efficiencyList = $this->getEfficiencySummaryPerArea($date);

        $lines = [];
        $lines[] = "⛔ Bad News {$formattedDate} - (⇀‸↼‶)";
        $lines[] = $divider;

        $lines[] = '*Astra, AI Number, Oli Detection, Detective AI:*';
        $lines[] = "- NG Processes: {$pokayokeNg}";
        $lines[] = '';

        $lines[] = '*Part:*';
        $lines[] = "- Telat Supply: {$scanData['telat_supply']}";
        $lines[] = "- Telat Request: {$scanData['telat_request']}";
        $lines[] = "- Missing DST: {$scanData['missing_dst']}";
        $lines[] = '';

        $lines[] = '*Marshalling Part Kurang:*';
        if (count($marshallingPartKurang) > 0) {
            foreach ($marshallingPartKurang as $areaName => $count) {
                $lines[] = "- {$areaName}: {$count}";
            }
        } else {
            $lines[] = '- Nihil';
        }
        $lines[] = '';

        $lines[] = '*Aspro:*';
        $lines[] = "- Total Audit: {$aspro['total_audit']}";
        $lines[] = "- Total Temuan: {$aspro['total_temuan']}";
        $lines[] = '';

        $lines[] = '*Rifa:*';
        $lines[] = "- Izin Sakit: {$rifaSakit} orang";
        $lines[] = '';

        $lines[] = '*Record NG:*';
        $lines[] = "- AI Number: {$ngData['chadet']} NG";
        $lines[] = "- Detective AI: {$ngData['parcom']} NG";
        $lines[] = '';

        $lines[] = '*Devmon:*';
        if (count($devmonUnsubmitted) > 0) {
            foreach ($devmonUnsubmitted as $dev) {
                $lines[] = "- {$dev['label']}: {$dev['last_user']}";
            }
        } else {
            $lines[] = '- Semua device sudah absen';
        }
        $lines[] = '';

        $lines[] = '*KYT ('.($kyt['week_label'] ?: 'Minggu Lalu').'):*';
        $lines[] = '- Belum Pengajuan: '.(count($kyt['belum_temuan']) ? implode(', ', $kyt['belum_temuan']) : 'Nihil');
        $lines[] = '- Belum Penanganan: '.(count($kyt['belum_penanganan']) ? implode(', ', $kyt['belum_penanganan']) : 'Nihil');
        $lines[] = '';

        $lines[] = '*Target Produksi:*';
        if (count($podiumMinus) > 0) {
            foreach ($podiumMinus as $pm) {
                $lines[] = "- {$pm}";
            }
        } else {
            $lines[] = '- Nihil / Semua Tercapai';
        }
        $lines[] = '';

        $lines[] = '*Efficiency:*';
        if (count($efficiencyList) > 0) {
            foreach ($efficiencyList as $eff) {
                $lines[] = "- {$eff}";
            }
        } else {
            $lines[] = '- Data tidak tersedia';
        }

        $lines[] = $divider;
        $lines[] = 'おつかれさまでした';
        $lines[] = '✧⁺⸜(･ ᗜ ･ )⸝⁺✧';

        return implode("\n", $lines);
    }

    /**
     * 1. Ambil jumlah ng_processes digital pokayoke hari ini
     */
    private function getPokayokeNgProcessesCount(Carbon $date): int
    {
        try {
            return DB::connection('podium')->table('ng_processes')
                ->whereDate('created_at', $date->toDateString())
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 2. Ambil data urgent telat supply, telat request, dan missing DST hari ini
     */
    private function getScanOperationalData(Carbon $date): array
    {
        try {
            $dateStr = $date->toDateString();
            $telatSupply = Mistake::whereDate('Day_Mistake', $dateStr)
                ->where('Category_Mistake', 'telat supply')
                ->count();

            $telatRequest = Mistake::whereDate('Day_Mistake', $dateStr)
                ->where('Category_Mistake', 'telat request')
                ->count();

            $workdaysAgo = SpecialDate::subWorkdays(Carbon::now(), 1);
            $missingDst = RequestModel::where('Status_Request', '!=', 'Done')
                ->whereNotNull('Ready_Request')
                ->get()
                ->filter(function ($req) use ($workdaysAgo) {
                    $time = $req->Design_Changes_Request ?? $req->Production_Area_Request ?? $req->Shipping_Request ?? $req->Ready_Request;

                    return $time && Carbon::parse($time)->lt($workdaysAgo);
                })->count();

            return [
                'telat_supply' => $telatSupply,
                'telat_request' => $telatRequest,
                'missing_dst' => $missingDst,
            ];
        } catch (\Throwable $e) {
            return ['telat_supply' => 0, 'telat_request' => 0, 'missing_dst' => 0];
        }
    }

    /**
     * 3. Ambil total audit dan temuan Aspro hari ini
     */
    private function getAsproOperationalData(Carbon $date): array
    {
        try {
            $dateStr = $date->toDateString();

            // Total audit di Aspro mencakup approval auditor pada jobdesc (list_reports),
            // training (list_trainings), dan jobdesc pengganti (list_report_replacements)
            $jobdescAudits = DB::connection('aspro')->table('list_reports')
                ->whereDate('Time_Approved_Auditor', $dateStr)
                ->count();

            $trainingAudits = 0;
            if (DB::connection('aspro')->getSchemaBuilder()->hasTable('list_trainings')) {
                $trainingAudits = DB::connection('aspro')->table('list_trainings')
                    ->whereDate('Time_Approved_Auditor', $dateStr)
                    ->count();
            }

            $replacementAudits = 0;
            if (DB::connection('aspro')->getSchemaBuilder()->hasTable('list_report_replacements')) {
                $replacementAudits = DB::connection('aspro')->table('list_report_replacements')
                    ->whereDate('Time_Approved_Auditor', $dateStr)
                    ->count();
            }

            $totalAudit = $jobdescAudits + $trainingAudits + $replacementAudits;

            $totalTemuan = DB::connection('aspro')->table('temuans')
                ->whereDate('Time_Temuan', $dateStr)
                ->count();

            return [
                'total_audit' => $totalAudit,
                'total_temuan' => $totalTemuan,
            ];
        } catch (\Throwable $e) {
            return ['total_audit' => 0, 'total_temuan' => 0];
        }
    }

    /**
     * 4. Ambil jumlah izin sakit Rifa hari ini
     */
    private function getRifaSickLeaveCount(Carbon $date): int
    {
        try {
            return DB::connection('rifa')->table('absensis')
                ->whereDate('tanggal', $date->toDateString())
                ->where('kategori', 'S')
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 5 & 6. Ambil record NG Parcom dan Chadet hari ini
     */
    private function getParcomAndChadetNgCount(Carbon $date): array
    {
        $dateStr = $date->toDateString();
        $parcomNg = 0;
        $chadetNg = 0;

        try {
            $parcomNg = DB::connection('parcom')->table('records')
                ->whereDate('Time_Record', $dateStr)
                ->where('Result_Record', 'NG')
                ->count();
        } catch (\Throwable $e) {
        }

        try {
            $chadetNg = DB::connection('chadet')->table('records')
                ->whereDate('Time', $dateStr)
                ->where('Status_Record', 'NG')
                ->count();
        } catch (\Throwable $e) {
        }

        return [
            'parcom' => $parcomNg,
            'chadet' => $chadetNg,
        ];
    }

    /**
     * 7. Ambil list device devmon yang belum absensi hari ini beserta last user-nya
     */
    private function getDevmonUnsubmittedDevices(Carbon $date): array
    {
        try {
            $dateStr = $date->toDateString();

            // ID device yang sudah absen hari ini
            $activeDeviceIds = DB::connection('devmon')->table('absences')
                ->whereDate('time_absence', $dateStr)
                ->pluck('device_id')
                ->unique()
                ->toArray();

            // Device yang aktif terdaftar tapi belum absen hari ini
            $unsubmitted = DB::connection('devmon')->table('phone_lists')
                ->where('approved', 1)
                ->where('registered', 1)
                ->whereNull('deleted_at')
                ->whereNotIn('model_id', $activeDeviceIds)
                ->select('id', 'model_id', 'model_name', 'model_type')
                ->orderBy('model_name')
                ->get();

            $result = [];
            foreach ($unsubmitted as $device) {
                // Cari user terakhir yang absen di device ini
                $lastAbsence = DB::connection('devmon')->table('absences')
                    ->where('device_id', $device->model_id)
                    ->orderBy('id', 'desc')
                    ->first();

                $lastUser = $lastAbsence && ! empty($lastAbsence->name)
                    ? $lastAbsence->name
                    : 'Belum pernah absen';

                $label = ! empty($device->model_name)
                    ? $device->model_name." ({$device->model_id})"
                    : $device->model_id;

                $result[] = [
                    'model_id' => $device->model_id,
                    'model_name' => $device->model_name,
                    'label' => $label,
                    'last_user' => $lastUser,
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 7b. Ambil rekap part kurang dari iseki_marshalling per area pada tanggal tertentu
     *
     * @return array<string, int> Contoh: ['Sub Assy' => 3, 'Main Line' => 1]
     */
    private function getMarshallingPartKurangSummary(Carbon $date): array
    {
        try {
            $rows = DB::connection('marshalling')->table('part_kurangs')
                ->whereDate('created_at', $date->toDateString())
                ->select('area', DB::raw('count(*) as total'))
                ->groupBy('area')
                ->orderBy('area')
                ->get();

            $result = [];
            foreach ($rows as $row) {
                $rawArea = trim((string) $row->area);
                $label = ucwords(str_replace('_', ' ', $rawArea));
                $result[$label] = (int) $row->total;
            }

            return $result;
        } catch (\Throwable $e) {
            \Log::error('Gagal mengambil data part_kurangs iseki_marshalling: '.$e->getMessage());

            return [];
        }
    }

    /**
     * 8. Ambil KYT belum input minggu lalu (temuan & penanganan) per area
     */
    private function getKytLastWeekUnsubmittedPerArea(Carbon $date): array
    {
        $belumTemuan = [];
        $belumPenanganan = [];
        $weekLabel = '';

        try {
            $lastWeek = DB::connection('kyt')->table('kyt_date_lists')
                ->where('kyt_date', '<', $date->toDateString())
                ->orderBy('kyt_date', 'desc')
                ->first();

            if ($lastWeek) {
                $weekLabel = 'Minggu '.$lastWeek->number_of_Weeks.' ('.Carbon::parse($lastWeek->kyt_date)->locale('id')->isoFormat('D MMMM Y').')';
                $teams = DB::connection('kyt')->table('team_k_y_t_s')->get();

                foreach ($teams as $team) {
                    $kyt = DB::connection('kyt')->table('k_y_t_lists')
                        ->where('team_k_y_t_id', $team->id)
                        ->where('kyt_date_id', $lastWeek->id)
                        ->first();

                    if (! $kyt) {
                        $belumTemuan[] = $team->team_name;
                    } else {
                        $penanganan = DB::connection('kyt')->table('kyt_penanganans')
                            ->where('kyt_list_id', $kyt->id)
                            ->first();

                        if (! $penanganan) {
                            $belumPenanganan[] = $team->team_name;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return [
            'week_label' => $weekLabel,
            'belum_temuan' => $belumTemuan,
            'belum_penanganan' => $belumPenanganan,
        ];
    }

    /**
     * 9. Ambil perolehan S minus Podium hari ini
     */
    private function getPodiumMinusSummary(Carbon $date): array
    {
        $minusList = [];

        try {
            $dateStr = $date->toDateString();
            $targets = DB::connection('podium')->table('wa_rangkuman_targets')
                ->where('Target_Date', $dateStr)
                ->get()
                ->keyBy(fn ($t) => $t->Category_Group.'|'.$t->Category_Item);

            $scanCount = function ($areaId) use ($date) {
                return DB::connection('efficiency')->table('scans')
                    ->where('Id_Area', $areaId)
                    ->whereDate('Time_Scan', $date)
                    ->distinct('Sequence_No_Plan')
                    ->count('Sequence_No_Plan');
            };

            $scanCountWithTractor = function ($areaId, $conditionRaw) use ($date) {
                return DB::connection('efficiency')->table('scans')
                    ->join('tractors as t', 'scans.Id_Tractor', '=', 't.Id_Tractor')
                    ->leftJoin(DB::raw('`iseki_podium`.`plans` as plans'), function ($join) {
                        $join->on('scans.Sequence_No_Plan', '=', 'plans.Sequence_No_Plan')
                            ->on('scans.Production_Date_Plan', '=', 'plans.Production_Date_Plan');
                    })
                    ->where('scans.Id_Area', $areaId)
                    ->whereDate('scans.Time_Scan', $date)
                    ->whereRaw($conditionRaw)
                    ->distinct('scans.Sequence_No_Plan')
                    ->count('scans.Sequence_No_Plan');
            };

            $scanCountWithPlan = function ($areaId, $conditionRaw) use ($date) {
                return DB::connection('efficiency')->table('scans')
                    ->leftJoin(DB::raw('`iseki_podium`.`plans` as plans'), function ($join) {
                        $join->on('scans.Sequence_No_Plan', '=', 'plans.Sequence_No_Plan')
                            ->on('scans.Production_Date_Plan', '=', 'plans.Production_Date_Plan');
                    })
                    ->where('scans.Id_Area', $areaId)
                    ->whereDate('scans.Time_Scan', $date)
                    ->whereRaw($conditionRaw)
                    ->distinct('scans.Sequence_No_Plan')
                    ->count('scans.Sequence_No_Plan');
            };

            $lineoffActual = DB::connection('podium')->table('plans')
                ->whereNotNull('Lineoff_Plan')
                ->whereDate('Lineoff_Plan', $dateStr)
                ->count();

            $sxg3SfTypesStr = "'SXG3','SXG3MW','SXG3日本','SF2','SF2 Trial','SF2CL','SF2CL日本','SF2MW','SF2MW日本','SF2日本','SF5','SF5MW'";

            $podiumItems = [
                ['group' => 'TRANSMISI', 'item' => 'SXG3 & SF', 'A' => $scanCountWithPlan(2, "(plans.Type_Plan IN ($sxg3SfTypesStr))")],
                ['group' => 'TRANSMISI', 'item' => 'Transmisi', 'A' => $scanCountWithPlan(2, "(plans.Type_Plan IS NULL OR plans.Type_Plan NOT IN ($sxg3SfTypesStr))")],
                ['group' => 'SUB ENGINE', 'item' => 'Sub Engine', 'A' => $scanCount(6)],
                ['group' => 'LINE A', 'item' => 'Unit', 'A' => $scanCountWithTractor(3, '(t.Name_Tractor = plans.Model_Name_Plan AND (plans.Model_Mower_Plan IS NULL OR t.Name_Tractor != plans.Model_Mower_Plan) AND (plans.Model_Collector_Plan IS NULL OR t.Name_Tractor != plans.Model_Collector_Plan))')],
                ['group' => 'LINE A', 'item' => 'Mocol', 'A' => $scanCountWithTractor(3, '(t.Name_Tractor = plans.Model_Mower_Plan OR t.Name_Tractor = plans.Model_Collector_Plan)')],
                ['group' => 'LINE B', 'item' => 'Line B', 'A' => $scanCount(4)],
                ['group' => 'SUB ASSY', 'item' => 'Sub Assy', 'A' => $scanCount(7)],
                ['group' => 'MAIN LINE', 'item' => 'Mainline', 'A' => $lineoffActual],
                ['group' => 'INSPEKSI', 'item' => 'Inspeksi', 'A' => $scanCount(8)],
                ['group' => 'MOCOL', 'item' => 'Unit', 'A' => $scanCountWithTractor(1, "scans.Sequence_No_Plan NOT REGEXP '[Tt]'")],
                ['group' => 'MOCOL', 'item' => 'Mower', 'A' => $scanCountWithTractor(1, "(scans.Sequence_No_Plan REGEXP '[Tt]' AND plans.Model_Name_Plan = plans.Model_Mower_Plan)")],
                ['group' => 'MOCOL', 'item' => 'Collector', 'A' => $scanCountWithTractor(1, "(scans.Sequence_No_Plan REGEXP '[Tt]' AND plans.Model_Name_Plan = plans.Model_Collector_Plan)")],
            ];

            foreach ($podiumItems as $pi) {
                $tKey = $pi['group'].'|'.$pi['item'];
                $tVal = isset($targets[$tKey]) ? (int) $targets[$tKey]->Target : 0;
                $sVal = $pi['A'] - $tVal;
                if ($sVal < 0) {
                    $minusList[] = "{$pi['group']} ({$pi['item']}): {$sVal}";
                }
            }
        } catch (\Throwable $e) {
        }

        return $minusList;
    }

    /**
     * 10. Ambil efisiensi harian per area seperti dashboard full screen
     */
    private function getEfficiencySummaryPerArea(Carbon $date): array
    {
        $effList = [];

        try {
            $areas = DB::connection('efficiency')->table('areas')
                ->orderByRaw("FIELD(Name_Area, 'TRANSMISI', 'SUB ENGINE', 'LINE A', 'LINE B', 'SUB ASSY', 'MAIN LINE', 'INSPEKSI', 'MOWER')")
                ->get();

            $calculateProgressiveHours = function (int $memberCount) {
                $now = Carbon::now();
                $start = Carbon::today()->setTime(7, 30);
                $endOfWork = Carbon::today()->setTime(16, 30);
                if ($now->lt($start)) {
                    return 0.0;
                }
                if ($now->gt($endOfWork)) {
                    return $memberCount * 8.0;
                }
                $totalHours = $start->diffInRealSeconds($now) / 3600.0;
                if ($now->gt(Carbon::today()->setTime(10, 0))) {
                    $totalHours -= 10 / 60;
                }
                if ($now->gt(Carbon::today()->setTime(12, 0))) {
                    $totalHours -= 40 / 60;
                }
                if ($now->gt(Carbon::today()->setTime(15, 0))) {
                    $totalHours -= 10 / 60;
                }

                return $memberCount * min(max(0.0, $totalHours), 8.0);
            };

            $todayReports = DB::connection('efficiency')->table('reports')
                ->where('Day_Report', $date->toDateString())
                ->get()
                ->keyBy('Id_Area');

            foreach ($areas as $area) {
                $areaId = $area->Id_Area;
                $scansSum = (float) DB::connection('efficiency')->table('scans')
                    ->where('Id_Area', $areaId)
                    ->whereDate('Time_Scan', $date)
                    ->sum('Assigned_Hour_Scan') * (1 - 0.078);

                $costsSum = (float) DB::connection('efficiency')->table('costs')
                    ->where('Id_Area', $areaId)
                    ->whereDate('Start_Cost', $date)
                    ->sum('Non_Operational_Cost');

                $penangananSum = (float) DB::connection('efficiency')->table('penanganans')
                    ->where('Id_Area', $areaId)
                    ->whereDate('Start_Penanganan', $date)
                    ->sum('Hour_Penanganan');

                $powerSum = (float) DB::connection('efficiency')->table('powers')
                    ->where('Id_Area', $areaId)
                    ->whereDate('Start_Power', $date)
                    ->sum('Leave_Hour_Power');

                $report = $todayReports->get($areaId);
                $repMembers = $report ? (int) $report->Total_Member_Report : 0;
                $memberHours = $calculateProgressiveHours($repMembers);

                $reportNetHours = $memberHours - $powerSum;
                $kategori1 = $reportNetHours + $penangananSum;
                $kategori2 = $scansSum + $costsSum;
                $selisihJamArea = $kategori2 - $kategori1;
                $effPercent = $scansSum != 0 ? ($selisihJamArea / $scansSum) * 100 : 0;

                $effList[] = "{$area->Name_Area}: ".number_format($effPercent, 0).'%';
            }
        } catch (\Throwable $e) {
        }

        return $effList;
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
            $key = $prefix.$c->Id_User;
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
            $key = $prefix.$r->Id_User;
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
            $key = $prefix.$rec->Id_User;
            $recordCounts[$key] = ($recordCounts[$key] ?? 0) + 1;
        }

        foreach ($branchRecords as $br) {
            $key = 'm_'.$br->Id_User;
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
            $lines[] = '-';
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
            $lines[] = '-';
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
            $lines[] = '-';
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
            $map['m_'.$m->Id_Member] = $m->Name_Member;
        }

        $users = User::all(['Id_User', 'Name_User']);
        foreach ($users as $u) {
            $map['u_'.$u->Id_User] = $u->Name_User;
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
