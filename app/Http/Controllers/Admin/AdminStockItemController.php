<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StockItem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdminStockItemController extends Controller
{
    /**
     * Tampilkan halaman Stock Item dengan tabel + search
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = StockItem::query();

        if ($search) {
            $query->where('Code_Rack_Stock_Item', 'LIKE', '%' . $search . '%');
        }

        $stockItems = $query->orderBy('Id_Stock_Item', 'desc')->get();

        return view('admins.stock_items.index', compact('stockItems', 'search'));
    }

    /**
     * Add manual — single atau multiple Code Rack
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

            // Cek duplikat
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

        return redirect()->route('stock_item')->with('success', $msg);
    }

    /**
     * Import dari file Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls',
        ]);

        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');

        $file = $request->file('excel');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $inserted = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $row) {
            // Kolom: No (index 0), Code Rack (index 1)
            if (count($row) >= 2) {
                $codeRack = strtoupper(trim($row[1] ?? ''));

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
        }

        $msg = "Import selesai: $inserted data baru ditambahkan.";
        if ($skipped > 0) {
            $msg .= " $skipped data dilewati (sudah ada).";
        }

        return redirect()->route('stock_item')->with('success', $msg);
    }

    /**
     * Export semua data sebagai Excel
     */
    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Code Rack');

        // Style Header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);

        // Data
        $stockItems = StockItem::orderBy('Id_Stock_Item')->get();

        $rowIndex = 2;
        $no = 1;
        foreach ($stockItems as $item) {
            $sheet->setCellValue("A{$rowIndex}", $no);
            $sheet->setCellValue("B{$rowIndex}", $item->Code_Rack_Stock_Item);
            $rowIndex++;
            $no++;
        }

        // Auto-size kolom
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'stock_items_export_' . now()->format('Ymd_His') . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        return response()->download($temp_file, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Download template kosong
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Code Rack');

        // Style Header
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);

        // Contoh data
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'CONTOH-RACK-001');
        $sheet->getStyle('A2:B2')->getFont()->setItalic(true);
        $sheet->getStyle('A2:B2')->getFont()->getColor()->setRGB('999999');

        // Auto-size kolom
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'stock_items_template.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        return response()->download($temp_file, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Hapus satu data
     */
    public function destroy($id)
    {
        $item = StockItem::findOrFail($id);
        $item->delete();

        return redirect()->route('stock_item')->with('success', 'Data berhasil dihapus.');
    }

    /**
     * Hapus banyak data (bulk)
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('stock_item')->with('error', 'Tidak ada data yang dipilih.');
        }

        StockItem::whereIn('Id_Stock_Item', $ids)->delete();

        $count = count($ids);
        return redirect()->route('stock_item')->with('success', "$count data berhasil dihapus.");
    }
}
