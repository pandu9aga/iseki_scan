<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Type_User;

class MainController extends Controller
{
    public function index(){
        if (session()->has('Id_User')) {
            if (session('Id_Type_User') == 2){
                return redirect()->route('dashboard');
            }
            else if (session('Id_Type_User') == 1){
                return redirect()->route('home');
            }
        }
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'Username_User' => 'required',
            'Password_User' => 'required'
        ]);

        $user = User::where('Username_User', $request->Username_User)->first();

        if (!$user) {
            return back()->withErrors(['loginError' => 'Invalid username or password']);
        }

        if ($request->Password_User == $user->Password_User) {
            session(['Id_User' => $user->Id_User]);
            session(['Id_Type_User' => $user->Id_Type_User]);
            session(['Username_User' => $user->Username_User]);
            if (session('Id_Type_User') == 2){
                return redirect()->route('dashboard');
            }
            else if (session('Id_Type_User') == 1){
                return redirect()->route('home');
            }
        }

        return back()->withErrors(['loginError' => 'Invalid username or password']);
    }

    public function logout()
    {
        session()->forget('Id_User');
        session()->forget('Id_Type_User');
        session()->forget('Username_User');
        return redirect()->route('/');
    }

    public function admin(){
        $type_user = Type_User::all();
        return view('admin', compact('type_user'));
    }

    public function create(Request $request){
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
        
        return redirect()->route('login');
    }
}