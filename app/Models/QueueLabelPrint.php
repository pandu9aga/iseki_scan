<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueLabelPrint extends Model
{
    // Define the table associated with the model
    protected $table = 'queue_label_prints';

    protected $connection = 'label';

    // Define the fillable attributes
    protected $fillable = [
        'rack_code',
        'label_type',
        'quantity',
        'requested_by',
        'area_name',
        'printed',
        'auto_print',
        'urgent',
    ];


    function getUrgentStatusAttribute()
    {
        return $this->urgent ? 'Yes' : 'No';
    }

    function RackList()
    {
        return $this->belongsTo(RackPartList::class, 'rack_code', 'rack_no');
    }
}
