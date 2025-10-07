<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'name','code','category_id','cost_price','selling_price','stock','picture'
    ];

    protected $casts = [
        'cost_price'    => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock'         => 'integer',
        'category_id'   => 'integer',
    ];

    public function getSellingPriceFormattedAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->selling_price, 0, ',', '.');
    }

    public function getPhotoUrlAttribute(): string
    {
        $p = (string) ($this->picture ?? '');

        if ($p && preg_match('#^https?://#i', $p)) {
            return $p;
        }
        if ($p && file_exists(public_path('storage/' . $p))) {
            return asset('storage/' . $p);
        }
        if ($p && file_exists(public_path('images/items/' . $p))) {
            return asset('images/items/' . $p);
        }

        return asset('images/no-image.png');
    }
}
