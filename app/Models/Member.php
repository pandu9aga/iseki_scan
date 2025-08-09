<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'members';
    protected $primaryKey = 'Id_Member';
    public $timestamps = false;

    protected $fillable = [
        'NIK_Member',
        'Name_Member',
    ];
}
