<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Rack;

class RackController extends Controller
{
    public function index(){
        $rack = Rack::all();
        return view('admins.racks.index', compact('rack'));
    }

    public function add()
    {
        return view('admins.racks.add');
    }

    public function create(Request $request)
    {
        // melakukan validasi data
        $request->validate([
            'Code_Rack' => 'required',
            'Code_Item_Rack' => 'required',
            'Name_Item_Rack' => 'required'
        ],
        [
            'Code_Rack.required' => 'Kode rack wajib diisi',
            'Code_Item_Rack.required' => 'Kode item wajib diisi',
            'Name_Item_Rack.required' => 'Nama item wajib diisi'
        ]);

        $now = Carbon::now()->format('Y-m-d H:i:s');
        
        //tambah data rack
        DB::table('racks')->insert([
            'Code_Rack' => $request->input('Code_Rack'),
            'Code_Item_Rack' => $request->input('Code_Item_Rack'),
            'Name_Item_Rack' => $request->input('Name_Item_Rack'),
            'Update_Rack' => $now
        ]);
        
        return redirect()->route('rack');
    }

    public function edit(Rack $Id_Rack)
    {
        return view('admins.racks.edit', compact('Id_Rack'));
    }

    public function update(Request $request, string $Id_Rack)
    {
        // melakukan validasi data
        $request->validate([
            'Code_Rack' => 'required',
            'Code_Item_Rack' => 'required',
            'Name_Item_Rack' => 'required'
        ],
        [
            'Code_Rack.required' => 'Kode rack wajib diisi',
            'Code_Item_Rack.required' => 'Kode item wajib diisi',
            'Name_Item_Rack.required' => 'Nama item wajib diisi'
        ]);
    
        $now = Carbon::now()->format('Y-m-d H:i:s');

        //update data rack
        DB::table('racks')->where('Id_Rack',$Id_Rack)->update([
            'Code_Rack' => $request->input('Code_Rack'),
            'Code_Item_Rack' => $request->input('Code_Item_Rack'),
            'Name_Item_Rack' => $request->input('Name_Item_Rack'),
            'Update_Rack' => $now
        ]);
                
        return redirect()->route('rack');
    }

    public function destroy(Rack $Id_Rack)
    {
        $Id_Rack->delete();
        
        return redirect()->route('rack')->with('success','Data berhasil di hapus' );
    }

    public function upload()
    {
        return view('admins.racks.upload'); // View form upload
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls'
        ]);

        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');

        $file = $request->file('excel');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $inserted = 0;
        $updated = 0;

        foreach (array_slice($rows, 1) as $row) {
            if (count($row) >= 3) {
                $codeRack = trim($row[0]);
                $codeItem = trim($row[1]);
                $nameItem = trim($row[2]);

                if (!empty($codeRack)) {
                    $exists = DB::table('racks')
                        ->where('Code_Rack', $codeRack)
                        ->first();

                    $data = [
                        'Code_Rack'      => $codeRack,
                        'Code_Item_Rack' => $codeItem,
                        'Name_Item_Rack' => $nameItem,
                        'Update_Rack'    => Carbon::now()->format('Y-m-d H:i:s'),
                    ];

                    if ($exists) {
                        DB::table('racks')
                            ->where('Code_Rack', $codeRack)
                            ->update($data);
                        $updated++;
                    } else {
                        DB::table('racks')->insert($data);
                        $inserted++;
                    }
                }
            }
        }

        return redirect()->back()->with(
            'success',
            "Import selesai: $inserted data baru ditambahkan, $updated data diperbarui."
        );
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Rack Code');
        $sheet->setCellValue('B1', 'Item Code');
        $sheet->setCellValue('C1', 'Item Name');

        // Data dari database
        $racks = DB::table('racks')
        ->select('Code_Rack', 'Code_Item_Rack', 'Name_Item_Rack')
        ->orderBy('Code_Rack')
        ->orderBy('Code_Item_Rack')
        ->get();

        $rowIndex = 2;
        foreach ($racks as $rack) {
            $sheet->setCellValue("A{$rowIndex}", $rack->Code_Rack);
            $sheet->setCellValue("B{$rowIndex}", $rack->Code_Item_Rack);
            $sheet->setCellValue("C{$rowIndex}", $rack->Name_Item_Rack);
            $rowIndex++;
        }

        // Simpan ke dalam output stream
        $writer = new Xlsx($spreadsheet);
        $fileName = 'racks_export_' . now()->format('Ymd_His') . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        return response()->download($temp_file, $fileName)->deleteFileAfterSend(true);
    }

    public function type(){
        $rack = Rack::select('Id_Rack', 'Code_Rack', 'Type_Tractor_Rack')->get();
        return view('admins.racks.type', compact('rack'));
    }

    public function typeEdit($Id_Rack)
    {
        $rack = Rack::where('Id_Rack', $Id_Rack)->first();
        return view('admins.racks.type_edit', compact('rack'));
    }

    public function typeUpdate(Request $request, $Id_Rack)
    {
        $request->validate([
            'Type_Tractor_Rack' => 'required'
        ], [
            'Type_Tractor_Rack.required' => 'Type Tractor wajib diisi'
        ]);

        DB::table('racks')->where('Id_Rack', $Id_Rack)->update([
            'Type_Tractor_Rack' => $request->input('Type_Tractor_Rack'),
            'Update_Rack' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        return redirect()->route('rack.type')->with('success', 'Type Tractor berhasil diperbarui');
    }

    public function typeUpload()
    {
        return view('admins.racks.type_upload');
    }

    public function typeImport(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls'
        ]);

        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');

        $file = $request->file('excel');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $updated = 0;

        foreach (array_slice($rows, 1) as $row) {
            if (count($row) >= 2) {
                $codeRack = trim($row[0]);
                $typeTractor = trim($row[1]);

                if (!empty($codeRack)) {
                    $exists = DB::table('racks')
                        ->where('Code_Rack', $codeRack)
                        ->first();

                    if ($exists) {
                        DB::table('racks')
                            ->where('Code_Rack', $codeRack)
                            ->update([
                                'Type_Tractor_Rack' => $typeTractor,
                                'Update_Rack' => Carbon::now()->format('Y-m-d H:i:s'),
                            ]);
                        $updated++;
                    }
                }
            }
        }

        return redirect()->route('rack.type')->with(
            'success',
            "Import selesai: $updated data Type Tractor diperbarui."
        );
    }

    public function typeExport()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Rack Code');
        $sheet->setCellValue('B1', 'Type Tractor');

        // Data dari database
        $racks = DB::table('racks')
            ->select('Code_Rack', 'Type_Tractor_Rack')
            ->orderBy('Code_Rack')
            ->get();

        $rowIndex = 2;
        foreach ($racks as $rack) {
            $sheet->setCellValue("A{$rowIndex}", $rack->Code_Rack);
            $sheet->setCellValue("B{$rowIndex}", $rack->Type_Tractor_Rack);
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'rack_types_export_' . now()->format('Ymd_His') . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        return response()->download($temp_file, $fileName)->deleteFileAfterSend(true);
    }
}
