<?php

namespace App\Infrastructure\Pricing;

use App\Domain\Pricing\MarketPrice;
use App\Domain\Pricing\PriceProvider;
use App\Domain\Pricing\PricingUnavailable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

final class GoldPriceOrgProvider implements PriceProvider
{
    public function name(): string { return 'GoldPrice.org'; }

    public function fetch(): MarketPrice
    {
        $payload = Http::acceptJson()->timeout(5)->retry(1, 150)
            ->get('https://data-asg.goldprice.org/dbXRates/PKR')->throw()->json();
        $ounce = data_get($payload, 'items.0.xauPrice');
        $timestamp = data_get($payload, 'ts');
        if (! is_numeric($ounce) || (float) $ounce <= 0) {
            throw new PricingUnavailable('GoldPrice.org returned an invalid price.');
        }
        $observedAt = is_numeric($timestamp)
            ? CarbonImmutable::createFromTimestampMs((int) $timestamp)
            : CarbonImmutable::now();
        return new MarketPrice((float) $ounce / 31.1034768, $this->name(), $observedAt);
    }
}
