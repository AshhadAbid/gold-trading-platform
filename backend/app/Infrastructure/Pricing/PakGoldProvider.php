<?php

namespace App\Infrastructure\Pricing;

use App\Domain\Pricing\MarketPrice;
use App\Domain\Pricing\PriceProvider;
use App\Domain\Pricing\PricingUnavailable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

final class PakGoldProvider implements PriceProvider
{
    public function name(): string { return 'PakGold'; }

    public function fetch(): MarketPrice
    {
        $html = Http::accept('text/html')->timeout(5)->retry(1, 150)->get('https://www.pakgold.net/')->throw()->body();
        $plain = preg_replace('/\s+/', ' ', strip_tags($html));
        $patterns = [
            '/24K\s+(?:per\s+gram|\/G)[^0-9]{0,30}(?:Rs\.?|PKR)?\s*([0-9,]+(?:\.\d+)?)/i',
            '/(?:per\s+gram|1\s*gram)[^0-9]{0,30}(?:Rs\.?|PKR)?\s*([0-9,]+(?:\.\d+)?)/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $plain, $match)) {
                $value = (float) str_replace(',', '', $match[1]);
                if ($value >= 1000 && $value <= 250000) {
                    return new MarketPrice($value, $this->name(), CarbonImmutable::now());
                }
            }
        }
        throw new PricingUnavailable('PakGold returned an unrecognised price response.');
    }
}
