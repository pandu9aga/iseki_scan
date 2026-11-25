<?php

namespace App\Http\Controllers\Helper;

use App\Models\Check;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Helper untuk memetakan area member ke zona rak pada database label
 * (iseki_label.rack_part_lists) dan menghitung part yang belum di-check
 * otomatis pada suatu tanggal.
 *
 * Mapping yang disepakati:
 *   BARAT    -> W
 *   TENGAH   -> M
 *   TIMUR    -> E
 *   PAINTING -> P
 */
class AreaRackHelper
{
    public const AREA_ZONES = [
        'BARAT'    => ['W'],
        'TENGAH'   => ['M'],
        'TIMUR'    => ['E'],
        'PAINTING' => ['P'],
    ];

    /**
     * Daftar area yang didukung.
     */
    public static function areaOptions(): array
    {
        return array_keys(self::AREA_ZONES);
    }

    public static function isValidArea(?string $area): bool
    {
        return $area !== null && array_key_exists($area, self::AREA_ZONES);
    }

    /**
     * Tentukan area dari satu kode zona rak (contoh: "W19", "GR", "M85", "E1", "P").
     */
    public static function areaFromZone(string $zone): ?string
    {
        $zone = strtoupper(trim($zone));
        if ($zone === '') {
            return null;
        }

        if ($zone[0] === 'W') {
            return 'BARAT';
        }
        if ($zone[0] === 'M') {
            return 'TENGAH';
        }
        if ($zone[0] === 'E') {
            return 'TIMUR';
        }
        if ($zone[0] === 'P') {
            return 'PAINTING';
        }

        return null;
    }

    /**
     * Parse kolom rack (JSON array, mis. ["W9","E10"]) menjadi daftar area unik.
     * Satu part multi-zona akan masuk ke semua area terkait.
     */
    public static function areasFromRack($rawRack): array
    {
        if (empty($rawRack)) {
            return [];
        }

        $zones = json_decode($rawRack, true);
        if (! is_array($zones)) {
            $zones = [trim((string) $rawRack, '[]"\' ')];
        }

        $areas = [];
        foreach ($zones as $zone) {
            $area = self::areaFromZone((string) $zone);
            if ($area !== null) {
                $areas[$area] = true;
            }
        }

        return array_keys($areas);
    }

    /**
     * Format nilai JSON array (rack / cell) menjadi teks yang rapi.
     */
    public static function formatField($raw): string
    {
        if (empty($raw)) {
            return '';
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return implode(', ', array_filter(array_map('trim', $decoded)));
        }

        return trim((string) $raw, '[]"\' ');
    }

    /**
     * Ambil semua part dari database label, dikelompokkan per area.
     * Part multi-zona dimasukkan ke semua area yang tercantum.
     *
     * @return array<string, array<int, array{rack_no:string,item_code:string,part_name:string,cell:string,zones:string}>>
     */
    public static function partsByArea(): array
    {
        $rows = DB::connection('label')
            ->table('rack_part_lists')
            ->select('rack_no', 'rack', 'cell', 'item_code', 'part_name')
            ->whereNotNull('rack')
            ->get();

        $result = array_fill_keys(self::areaOptions(), []);

        foreach ($rows as $row) {
            $areas = self::areasFromRack($row->rack);
            if (empty($areas)) {
                continue;
            }

            $item = [
                'rack_no'   => (string) $row->rack_no,
                'item_code' => (string) ($row->item_code ?? ''),
                'part_name' => trim((string) ($row->part_name ?? '')),
                'cell'      => self::formatField($row->cell),
                'zones'     => self::formatField($row->rack),
            ];

            foreach ($areas as $area) {
                $result[$area][] = $item;
            }
        }

        foreach ($result as $area => $items) {
            usort($items, fn ($a, $b) => strcmp($a['rack_no'], $b['rack_no']));
            $result[$area] = $items;
        }

        return $result;
    }

    /**
     * Rack yang sudah di-check OTOMATIS pada tanggal tertentu.
     * Hanya Auto_Check = 1 (hasil scan) yang dihitung.
     *
     * @return array<string,bool> set Code_Rack => true
     */
    public static function checkedRackSet(Carbon $date): array
    {
        $codes = Check::where('Auto_Check', 1)
            ->whereDate('Time_Check', $date->toDateString())
            ->distinct()
            ->pluck('Code_Rack')
            ->all();

        $set = [];
        foreach ($codes as $code) {
            $set[strtoupper(trim((string) $code))] = true;
        }

        return $set;
    }

    /**
     * Daftar part pada satu area beserta status check-nya, difilter sesuai status.
     *
     * @param  string  $status  all | checked | unchecked
     */
    public static function itemsForArea(string $area, array $partsByArea, array $checkedSet, string $status = 'unchecked'): array
    {
        $items = $partsByArea[$area] ?? [];
        $result = [];

        foreach ($items as $item) {
            $isChecked = isset($checkedSet[strtoupper($item['rack_no'])]);

            if ($status === 'checked' && ! $isChecked) {
                continue;
            }
            if ($status === 'unchecked' && $isChecked) {
                continue;
            }

            $item['checked'] = $isChecked;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Daftar part pada satu area yang belum di-check otomatis.
     */
    public static function uncheckedItems(string $area, array $partsByArea, array $checkedSet): array
    {
        return self::itemsForArea($area, $partsByArea, $checkedSet, 'unchecked');
    }

    /**
     * Ringkasan total / sudah / belum per area untuk tanggal tertentu.
     *
     * @return array<string, array{total:int,checked:int,unchecked:int}>
     */
    public static function summaryPerArea(array $partsByArea, array $checkedSet): array
    {
        $summary = [];

        foreach (self::areaOptions() as $area) {
            $items = $partsByArea[$area] ?? [];
            $total = count($items);
            $checked = 0;

            foreach ($items as $item) {
                if (isset($checkedSet[strtoupper($item['rack_no'])])) {
                    $checked++;
                }
            }

            $summary[$area] = [
                'total'     => $total,
                'checked'   => $checked,
                'unchecked' => $total - $checked,
            ];
        }

        return $summary;
    }
}
