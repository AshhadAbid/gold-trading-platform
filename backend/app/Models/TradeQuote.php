<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class TradeQuote extends Model
{
    use HasUuids;
    protected $fillable = ['portfolio_id', 'side', 'gold_grams', 'market_price_pkr', 'unit_price_pkr', 'total_pkr', 'price_source', 'price_observed_at', 'expires_at'];
    protected function casts(): array { return ['gold_grams'=>'decimal:6','market_price_pkr'=>'decimal:2','unit_price_pkr'=>'decimal:2','total_pkr'=>'decimal:2','price_observed_at'=>'immutable_datetime','expires_at'=>'immutable_datetime','confirmed_at'=>'immutable_datetime']; }
}
