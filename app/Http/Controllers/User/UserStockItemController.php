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

        $memberIds = $stockItems->pluck('Id_Member')->filter()->unique()->values();
        $members = $memberIds->isNotEmpty()
            ? \App\Models\Member::whereIn('Id_Member', $memberIds)->pluck('Name_Member', 'Id_Member')->toArray()
            : [];

        return view('users.stock_items.index', compact('stockItems', 'search', 'members'));
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
                'Id_Member' => session('Id_Member'),
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

        // Hanya boleh menghapus data yang diinputkan oleh member sendiri
        if ((int) $item->Id_Member !== (int) session('Id_Member')) {
            return redirect()->route('user_stock_item')->with('error', 'Anda hanya dapat menghapus data yang Anda inputkan sendiri.');
        }

        $item->delete();

        return redirect()->route('user_stock_item')->with('success', 'Data berhasil dihapus.');
    }

    /**
     * Hapus banyak data (bulk).
     */
    public function bulkDestroy(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        if (empty($ids)) {
            return redirect()->route('user_stock_item')->with('error', 'Tidak ada data yang dipilih.');
        }

        // Hanya boleh menghapus data yang diinputkan oleh member sendiri
        $deleted = StockItem::whereIn('Id_Stock_Item', $ids)
            ->where('Id_Member', session('Id_Member'))
            ->delete();

        if ($deleted === 0) {
            return redirect()->route('user_stock_item')->with('error', 'Data yang dipilih bukan milik Anda.');
        }

        return redirect()->route('user_stock_item')->with('success', "$deleted data berhasil dihapus.");
    }
}
