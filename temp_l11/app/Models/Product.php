<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'IdProduct';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;
    protected $fillable = [
        'IdProduct',
        'IdCategory',
        'NameProduct',
        'Decription' 
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'IdProduct', 'IdProduct');
    }
}