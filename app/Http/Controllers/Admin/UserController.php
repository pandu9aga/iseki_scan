<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Type_User;

class UserController extends Controller
{
    public function index(){
        $user = User::all();
        $type_user = Type_User::all();
        return view('admins.users.index', compact('user','type_user'));
    }

    public function add()
    {
        $type_user = Type_User::all();
        return view('admins.users.add', compact('type_user'));
    }

    public function create(Request $request)
    {
        // melakukan validasi data
        $request->validate([
            'Name_User' => 'required',
            'Username_User' => 'required|unique:users,Username_User',
            'Password_User' => 'required',
            'Id_Type_User' => 'required'
        ],
        [
            'Name_User.required' => 'Nama wajib diisi',
            'Username_User.required' => 'Username wajib diisi',
            'Username_User.unique' => 'Username sudah digunakan, pilih yang lain',
            'Password_User.required' => 'Password wajib diisi',
            'Id_Type_User.required' => 'Type User wajib diisi'
        ]);
        
        //tambah data user
        DB::table('users')->insert([
            'Name_User' => $request->input('Name_User'),
            'Username_User' => $request->input('Username_User'),
            'Password_User' => $request->input('Password_User'),
            'Id_Type_User' => $request->input('Id_Type_User')
        ]);
        
        return redirect()->route('user');
    }

    public function edit(User $Id_User)
    {
        $type_user = Type_User::all();
        return view('admins.users.edit', compact('Id_User','type_user'));
    }

    public function update(Request $request, string $Id_User)
    {
        // melakukan validasi data
        $request->validate([
            'Name_User' => 'required',
            'Username_User' => 'required|unique:users,Username_User,' . $Id_User . ',Id_User',
            'Password_User' => 'required',
            'Id_Type_User' => 'required'
        ],
        [
            'Name_User.required' => 'Nama wajib diisi',
            'Username_User.required' => 'Username wajib diisi',
            'Username_User.unique' => 'Username sudah digunakan, pilih yang lain',
            'Password_User.required' => 'Password wajib diisi',
            'Id_Type_User.required' => 'Type User wajib diisi'
        ]);
    
        //update data user
        DB::table('users')->where('Id_User',$Id_User)->update([
            'Name_User' => $request->input('Name_User'),
            'Username_User' => $request->input('Username_User'),
            'Password_User' => $request->input('Password_User'),
            'Id_Type_User' => $request->input('Id_Type_User')
        ]);
                
        return redirect()->route('user');
    }

    public function destroy(User $Id_User)
    {
        $Id_User->delete();
        
        return redirect()->route('user')->with('success','Data berhasil di hapus' );
    }
}
