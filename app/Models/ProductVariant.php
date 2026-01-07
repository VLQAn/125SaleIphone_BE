<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $table = 'product_variants';
    protected $primaryKey = 'IdProductVar';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;
    protected $fillable = [
        'IdProductVar',
        'IdProduct',
        'Color',
        'Price',
        'ImgPath',
        'Stock'
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'IdProduct', 'IdProduct');
    }
}