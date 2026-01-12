<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'IdOrderItem';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'IdOrderItem',
        'IdOrder',
        'IdProduct',
        'Quantity',
        'UnitPrice'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'IdOrder', 'IdOrder');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'IdProduct', 'IdProduct');
    }
}
