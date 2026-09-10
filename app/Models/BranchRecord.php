<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchRecord extends Model
{
    use HasFactory;

    protected $table = 'branch_records';
    protected $primaryKey = 'Id_Branch_Record';

    public $timestamps = false;

    protected $fillable = [
        'Day_Branch_Record',
        'Time_Branch_Record',
        'Code_Rack',
        'Id_User',
        'Updated_At_Branch_Record',
    ];

    // Relasi ke Member
    public function member()
    {
        return $this->belongsTo(Member::class, 'Id_User', 'Id_Member');
    }

    // Relasi ke Rack
    public function rack()
    {
        return $this->belongsTo(Rack::class, 'Code_Rack', 'Code_Rack');
    }

    // Helper attribute for display name
    public function getDisplayNameAttribute()
    {
        return optional($this->member)->Name_Member ?? '';
    }
}
