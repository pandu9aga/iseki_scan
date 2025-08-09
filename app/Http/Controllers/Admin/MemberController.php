<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Type_User; // jika ada tipe user untuk member
use Illuminate\Support\Facades\Session;

class MemberController extends Controller
{
    // Tampilkan daftar member
    public function index()
    {
        $members = Member::all();
        $type_user = Type_User::all(); // kalau pakai tipe user untuk member
        return view('admins.members.index', compact('members', 'type_user'));
    }

    // Form tambah member
    public function add()
    {
        $type_user = Type_User::all();
        return view('admins.members.add', compact('type_user'));
    }

    // Simpan member baru
    public function create(Request $request)
    {
        $request->validate([
            'NIK_Member' => 'required|unique:members,NIK_Member',
            'Name_Member' => 'required'
        ], [
            'NIK_Member.required' => 'NIK wajib diisi',
            'NIK_Member.unique' => 'NIK sudah terdaftar',
            'Name_Member.required' => 'Nama wajib diisi'
        ]);

        Member::create([
            'NIK_Member' => $request->NIK_Member,
            'Name_Member' => $request->Name_Member,
        ]);

        return redirect()->route('member')->with('success', 'Data member berhasil ditambah');
    }

    // Form edit member
    public function edit($id)
    {
        $member = Member::findOrFail($id);
        $type_user = Type_User::all();
        return view('admins.members.edit', compact('member', 'type_user'));
    }

    // Update data member
    public function update(Request $request, $id)
    {
        $request->validate([
            'NIK_Member' => 'required|unique:members,NIK_Member,' . $id . ',Id_Member',
            'Name_Member' => 'required'
        ], [
            'NIK_Member.required' => 'NIK wajib diisi',
            'NIK_Member.unique' => 'NIK sudah terdaftar',
            'Name_Member.required' => 'Nama wajib diisi'
        ]);

        $member = Member::findOrFail($id);
        $member->update([
            'NIK_Member' => $request->NIK_Member,
            'Name_Member' => $request->Name_Member
        ]);

        return redirect()->route('member')->with('success', 'Data member berhasil diupdate');
    }

    // Hapus member
    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $member->delete();

        return redirect()->route('member')->with('success', 'Data member berhasil dihapus');
    }
}
