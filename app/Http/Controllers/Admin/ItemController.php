<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Item;

class ItemController extends Controller
{
    public function index(){
        $item = Item::all();
        return view('admins.items.index', compact('item'));
    }

    public function add()
    {
        return view('admins.items.add');
    }

    public function create(Request $request)
    {
        // melakukan validasi data
        $request->validate([
            'Name_Item' => 'required',
            'Code_Item' => 'required'
        ],
        [
            'Name_Item.required' => 'Nama item wajib diisi',
            'Code_Item.required' => 'Kode item wajib diisi'
        ]);
        
        //tambah data item
        DB::table('items')->insert([
            'Name_Item' => $request->input('Name_Item'),
            'Code_Item' => $request->input('Code_Item')
        ]);
        
        return redirect()->route('item');
    }

    public function edit(Item $Id_Item)
    {
        return view('admins.items.edit', compact('Id_Item'));
    }

    public function update(Request $request, string $Id_Item)
    {
        // melakukan validasi data
        $request->validate([
            'Name_Item' => 'required',
            'Code_Item' => 'required'
        ],
        [
            'Name_Item.required' => 'Nama item wajib diisi',
            'Code_Item.required' => 'Kode item wajib diisi'
        ]);
    
        //update data item
        DB::table('items')->where('Id_Item',$Id_Item)->update([
            'Name_Item' => $request->input('Name_Item'),
            'Code_Item' => $request->input('Code_Item')
        ]);
                
        return redirect()->route('item');
    }

    public function destroy(Item $Id_Item)
    {
        $Id_Item->delete();
        
        return redirect()->route('item')->with('success','Data berhasil di hapus' );
    }
}
