<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RackPartList extends Model
{
    // Define the table associated with the model
    protected $table = 'rack_part_lists';

    protected $connection = 'label';

    // Define the fillable attributes
    protected $fillable = [
        'rack_no',
        'item_code',
        'part_name',
        'cek',
        'supplier',
        'part_hydrolis',
        'type_tractor',
        'satuan',
        'sample',
    ];
}
