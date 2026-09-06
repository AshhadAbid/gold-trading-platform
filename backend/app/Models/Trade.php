<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Trade extends Model
{
    use HasUuids;
    protected $fillable = ['quote_id','portfolio_id','side','gold_grams','unit_price_pkr','total_pkr','cash_balance_pkr','gold_balance_grams','platform_gold_balance_grams'];
    protected function casts(): array { return ['gold_grams'=>'decimal:6','unit_price_pkr'=>'decimal:2','total_pkr'=>'decimal:2','cash_balance_pkr'=>'decimal:2','gold_balance_grams'=>'decimal:6','platform_gold_balance_grams'=>'decimal:6']; }
}
