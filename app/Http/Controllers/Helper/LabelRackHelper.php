<?php

namespace App\Http\Controllers\Helper;

use App\Models\RackPartList;

class LabelRackHelper
{
    /**
     * Decode and format JSON / raw string value from rack_part_lists (rack or cell).
     */
    public static function formatField($val): string
    {
        if (!$val) {
            return '';
        }
        $decoded = json_decode($val, true);
        if (is_array($decoded)) {
            return implode(', ', array_filter(array_map('trim', $decoded)));
        }
        return trim($val, '[]"\' ');
    }

    /**
     * Build 'No Rack' string from raw rack & cell fields.
     */
    public static function buildNoRack($rawRack, $rawCell): string
    {
        $rackStr = self::formatField($rawRack);
        $cellStr = self::formatField($rawCell);

        if ($rackStr !== '' && $cellStr !== '') {
            return $rackStr . ' - ' . $cellStr;
        }

        return $rackStr ?: ($cellStr ?: '-');
    }

    /**
     * Build mapping [ 'Code_Rack##Code_Item_Rack' => 'No Rack', 'Code_Rack' => 'No Rack' ]
     * for a collection / array of requests or rack items to prevent N+1 queries.
     *
     * @param \Illuminate\Support\Collection|array $requests
     * @return array
     */
    public static function getNoRackMap($requests): array
    {
        if (empty($requests)) {
            return [];
        }

        $rackNos = [];
        foreach ($requests as $r) {
            $codeRack = is_array($r) ? ($r['Code_Rack'] ?? null) : ($r->Code_Rack ?? null);
            if ($codeRack) {
                $rackNos[] = $codeRack;
            }
        }

        $rackNos = array_values(array_unique(array_filter($rackNos)));
        if (empty($rackNos)) {
            return [];
        }

        $labelList = RackPartList::whereIn('rack_no', $rackNos)
            ->get(['rack_no', 'item_code', 'rack', 'cell']);

        $map = [];
        foreach ($labelList as $l) {
            $noRack = self::buildNoRack($l->rack, $l->cell);

            if ($l->item_code) {
                $map[$l->rack_no . '##' . $l->item_code] = $noRack;
            }
            if (!isset($map[$l->rack_no])) {
                $map[$l->rack_no] = $noRack;
            }
        }

        return $map;
    }

    /**
     * Resolve No Rack for a single item from preloaded map.
     */
    public static function resolveNoRack(?string $codeRack, ?string $codeItem, array $map): string
    {
        if (!$codeRack) {
            return '-';
        }
        if ($codeItem && isset($map[$codeRack . '##' . $codeItem])) {
            return $map[$codeRack . '##' . $codeItem];
        }
        if (isset($map[$codeRack])) {
            return $map[$codeRack];
        }
        return '-';
    }
}
