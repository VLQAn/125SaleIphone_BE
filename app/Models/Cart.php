<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $table = 'carts';
    protected $primaryKey = 'IdCart';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['IdCart', 'IdUser'];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'IdUser', 'IdUser');
    }
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'IdCart', 'IdCart');
    }
}