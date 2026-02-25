<?php

namespace App\Http\Controllers\Helper;

use App\Models\Member;
use App\Models\User;

trait AuthHelper
{
    public function getUser(){
        if(session()->has('Id_User')) {
            return User::where('Id_User', session('Id_User'))->first();
        }
        if(session()->has('Id_Member')) {
            return Member::where('Id_Member', session('Id_Member'))->first();
        }
        return null;
    }
}
