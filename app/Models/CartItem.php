<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items';
    protected $primaryKey = 'IdCartItem';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['IdCartItem', 'IdCart', 'IdProduct', 'Quantity'];

    public function product() {
        return $this->belongsTo(Product::class, 'IdProduct', 'IdProduct');
    }
}