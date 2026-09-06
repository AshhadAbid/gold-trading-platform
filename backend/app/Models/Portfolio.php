<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Portfolio extends Model
{
    protected $fillable = ['owner', 'cash_pkr', 'gold_grams', 'platform_gold_grams'];
    protected function casts(): array { return ['cash_pkr' => 'decimal:2', 'gold_grams' => 'decimal:6', 'platform_gold_grams' => 'decimal:6']; }
}
