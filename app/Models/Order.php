<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'IdOrder';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['IdOrder', 'IdUser', 'TotalPrice', 'Status'];
    const STATUS_MAP = [
        0 => 'Đang xử lý',
        1 => 'Đang giao hàng',
        2 => 'Đã giao hàng',
        3 => 'Hoàn thành',
        4 => 'Đã huỷ'
    ];
     public function address() {
        return $this->hasOne(OrderAddress::class, 'IdOrder', 'IdOrder');
    }
    public function items() {
        return $this->hasMany(OrderItem::class, 'IdOrder', 'IdOrder');
    }
}