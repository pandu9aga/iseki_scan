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
        'Status_Non_Active',
        'Member_Area',
    ];

    /**
     * Daftar nilai enum kolom members.Member_Area, dibaca langsung dari database
     * supaya daftar area cukup diubah di satu tempat (kolom enum-nya).
     */
    public static function areaOptions(): array
    {
        try {
            $column = \Illuminate\Support\Facades\DB::selectOne("SHOW COLUMNS FROM `members` WHERE Field = 'Member_Area'");

            if (!$column || !preg_match('/^enum\((.*)\)$/i', $column->Type, $m)) {
                return [];
            }

            return str_getcsv($m[1], ',', "'", '');
        } catch (\Throwable $e) {
            return [];
        }
    }
}
