<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    protected $table = 'order_addresses';
    protected $primaryKey = 'IdOrderAdd';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'IdOrderAdd',
        'IdOrder',
        'FullName',
        'Phone',
        'Address'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'IdOrder', 'IdOrder');
    }
}