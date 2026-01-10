<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $table = 'cart_items';
    protected $primaryKey = 'IdCartItem';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'IdCartItem',
        'IdCart',
        'IdProduct',
        'Quantity'];
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'IdCart', 'IdCart');
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'IdProduct', 'IdProduct');
    }
}