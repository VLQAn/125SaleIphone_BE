<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'carts';
    protected $primaryKey = 'IdCart';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['IdCart', 'IdUser'];

    public function items() {
        return $this->hasMany(CartItem::class, 'IdCart', 'IdCart');
    }
}