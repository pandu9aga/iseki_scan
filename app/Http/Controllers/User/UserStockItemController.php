<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockItem;

class UserStockItemController extends Controller
{
    /**
     * Tampilkan halaman Stock Item member dengan tabel + search.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = StockItem::query();

        if ($search) {
            $query->where('Code_Rack_Stock_Item', 'LIKE', '%' . $search . '%');
        }

        $stockItems = $query->orderBy('Id_Stock_Item', 'desc')->get();

        return view('users.stock_items.index', compact('stockItems', 'search'));
    }

    /**
     * Add manual — single atau multiple Code Rack.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code_racks' => 'required|string',
        ], [
            'code_racks.required' => 'Code Rack wajib diisi.',
        ]);

        $lines = preg_split('/[\r\n,;]+/', $request->input('code_racks'));
        $inserted = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $codeRack = strtoupper(trim($line));
            if (empty($codeRack)) continue;

            $exists = StockItem::where('Code_Rack_Stock_Item', $codeRack)->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            StockItem::create([
                'Code_Rack_Stock_Item' => $codeRack,
            ]);
            $inserted++;
        }

        $msg = "$inserted data berhasil ditambahkan.";
        if ($skipped > 0) {
            $msg .= " $skipped data dilewati (sudah ada).";
        }

        return redirect()->route('user_stock_item')->with('success', $msg);
    }

    /**
     * Hapus satu data.
     */
    public function destroy($id)
    {
        $item = StockItem::findOrFail($id);
        $item->delete();

        return redirect()->route('user_stock_item')->with('success', 'Data berhasil dihapus.');
    }

    /**
     * Hapus banyak data (bulk).
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('user_stock_item')->with('error', 'Tidak ada data yang dipilih.');
        }

        StockItem::whereIn('Id_Stock_Item', $ids)->delete();

        $count = count($ids);
        return redirect()->route('user_stock_item')->with('success', "$count data berhasil dihapus.");
    }
}
