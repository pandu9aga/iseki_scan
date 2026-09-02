<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpecialDate extends Model
{
    protected $connection = 'rifa';
    protected $table = 'special_dates';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'tanggal',
        'jenis_tanggal',
    ];

    const HOLIDAY_TYPES = ['libur nasional', 'cuti perusahaan', 'libur pengganti'];
    const FORCED_WORKDAY_TYPE = 'libur masuk';
    const ADOPTED_SINCE = '2026-08-01'; // Aturan ini berlaku sejak tanggal ini

    /**
     * Cache di level request untuk mencegah hit berulang ke file/Redis/DB di loop yang sama.
     */
    protected static $requestCache = null;
    protected static $workScheduleCache = null;
    protected static $dayMemoCache = [];

    /**
     * Inisialisasi data override dari tabel local `work_schedules`
     * Diload sekali ke memori per-request untuk mencegah N+1 query.
     */
    public static function loadWorkScheduleData()
    {
        if (self::$workScheduleCache !== null) {
            return self::$workScheduleCache;
        }

        try {
            $schedules = \App\Models\WorkSchedule::all();
            $map = [];
            foreach ($schedules as $ws) {
                $d = Carbon::parse($ws->tanggal)->format('Y-m-d');
                $map[$d] = (bool) $ws->is_libur;
            }
            self::$workScheduleCache = $map;
        } catch (\Exception $e) {
            self::$workScheduleCache = [];
        }

        return self::$workScheduleCache;
    }

    /**
     * Inisialisasi data dari database `rifa` (dengan fallback)
     * Menggunakan cache Laravel lintas-request dan static property per-request.
     */
    public static function loadData()
    {
        if (self::$requestCache !== null) {
            return self::$requestCache;
        }

        // Cache lintas-request selama 6 jam (21600 detik)
        self::$requestCache = Cache::remember('special_dates_map', 21600, function () {
            try {
                // Precedence: Jika ada duplikat tanggal, libur akan menimpa 'libur masuk' 
                // karena order by 'jenis_tanggal' atau kita process di array.
                // Mari kita ambil semua dan masukkan ke array
                $dates = self::orderBy('tanggal', 'asc')->get();

                $map = [];
                foreach ($dates as $date) {
                    $d = Carbon::parse($date->tanggal)->format('Y-m-d');
                    // Jika tanggal ini sudah di set sebagai holiday, jangan di-override oleh "libur masuk"
                    if (isset($map[$d]) && in_array($map[$d], self::HOLIDAY_TYPES)) {
                        continue;
                    }
                    $map[$d] = $date->jenis_tanggal;
                }
                return $map;
            } catch (\Exception $e) {
                // Fallback: Jika DB rifa mati, catat error dan kembalikan array kosong (akan fallback ke aturan lama)
                Log::error('Gagal mengambil data special_dates dari DB rifa: ' . $e->getMessage());
                return [];
            }
        });

        return self::$requestCache;
    }

    /**
     * Cek apakah sebuah tanggal adalah hari kerja.
     * Menggunakan array statis agar lookup O(1) super cepat tanpa N+1 query.
     *
     * @param string|Carbon $date
     * @return bool
     */
    public static function isWorkday($date): bool
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $dateString = $date->format('Y-m-d');

        // Memoization cepat: jika tanggal ini sudah dihitung dalam request yang sama, langsung kembalikan hasilnya
        if (isset(self::$dayMemoCache[$dateString])) {
            return self::$dayMemoCache[$dateString];
        }

        // Aturan lama (sebelum cutoff date): hanya skip weekend
        if ($dateString < self::ADOPTED_SINCE) {
            return self::$dayMemoCache[$dateString] = !$date->isWeekend();
        }

        // 1. Override dari Work Schedule (Prioritas Tertinggi) - O(1) in-memory lookup
        $workSchedules = self::loadWorkScheduleData();
        if (isset($workSchedules[$dateString])) {
            return self::$dayMemoCache[$dateString] = !$workSchedules[$dateString];
        }

        // 2. Data dari SpecialDate (rifa) - O(1) in-memory lookup
        $specialDates = self::loadData();
        $type = $specialDates[$dateString] ?? null;

        if ($type) {
            if (in_array($type, self::HOLIDAY_TYPES)) {
                return self::$dayMemoCache[$dateString] = false; // Libur (merah/cuti/pengganti)
            }
            if ($type === self::FORCED_WORKDAY_TYPE) {
                return self::$dayMemoCache[$dateString] = true; // Weekend tapi dipaksa masuk
            }
        }

        // Jika tidak ada di special_dates, maka hari kerja adalah weekday biasa
        return self::$dayMemoCache[$dateString] = !$date->isWeekend();
    }

    /**
     * Peta hari kerja untuk rentang tertentu (O(1) lookup di dalam loop).
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return array [ 'Y-m-d' => true/false ]
     */
    public static function workdayMapForRange(Carbon $start, Carbon $end): array
    {
        $map = [];
        $current = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($current->lte($endDay)) {
            $dateStr = $current->format('Y-m-d');
            $map[$dateStr] = self::isWorkday($current);
            $current->addDay();
        }

        return $map;
    }

    /**
     * Kurangi sejumlah hari kerja (skip libur/weekend).
     * 
     * @param Carbon $date
     * @param int $days
     * @return Carbon
     */
    public static function subWorkdays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();
        $daysCounted = 0;
        
        while ($daysCounted < $days) {
            $result->subDay();
            if (self::isWorkday($result)) {
                $daysCounted++;
            }
        }
        
        return $result;
    }

    /**
     * Tambah sejumlah hari kerja (skip libur/weekend, include libur masuk).
     * 
     * @param Carbon $date
     * @param int $days
     * @return Carbon
     */
    public static function addWorkdays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();
        $daysCounted = 0;
        
        while ($daysCounted < $days) {
            $result->addDay();
            if (self::isWorkday($result)) {
                $daysCounted++;
            }
        }
        
        return $result;
    }

    // ─── Request Cutoff Logic ────────────────────────────────────────

    const REQUEST_CUTOFF_TIME = '15:30:00';
    const NEXT_DAY_REQUEST_TIME = '07:45:00';

    /**
     * Tentukan "waktu efektif" sebuah request.
     * 
     * - Jika hari ini BUKAN hari kerja → geser ke hari kerja berikutnya jam 07:45
     * - Jika submit >= cutoff (15:30) → geser ke hari kerja berikutnya jam 07:45
     * - Selain itu → dianggap normal (hari ini, jam submit asli)
     *
     * @param Carbon $now  Waktu submit asli
     * @return array ['date' => 'Y-m-d', 'time' => 'H:i:s', 'shifted' => bool]
     */
    public static function resolveEffectiveRequestTime(Carbon $now): array
    {
        $isTodayWorkday = self::isWorkday($now);
        $cutoff = $now->copy()->setTimeFromTimeString(self::REQUEST_CUTOFF_TIME);

        if (!$isTodayWorkday || $now->gte($cutoff)) {
            $nextWorkday = self::addWorkdays($now, 1);
            return [
                'date' => $nextWorkday->format('Y-m-d'),
                'time' => self::NEXT_DAY_REQUEST_TIME,
                'shifted' => true,
            ];
        }

        return [
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i:s'),
            'shifted' => false,
        ];
    }
}
