<?php

namespace App\Application\Pricing;

use App\Domain\Pricing\MarketPrice;
use App\Domain\Pricing\PriceProvider;
use App\Domain\Pricing\PricingUnavailable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class MarketPriceService
{
    public function __construct(private readonly iterable $providers) {}

    public function current(): MarketPrice
    {
        if (config('trading.price_mode') === 'all_down') {
            throw new PricingUnavailable('Live pricing is temporarily unavailable. Trading is paused.');
        }
        $cached = Cache::get('market-price:24k-pkr-gram');
        if (is_array($cached)) {
            return new MarketPrice($cached['pkr_per_gram'], $cached['source'], CarbonImmutable::parse($cached['observed_at']));
        }

        return Cache::lock('market-price:24k-pkr-gram:refresh', 10)->block(6, function (): MarketPrice {
            $cached = Cache::get('market-price:24k-pkr-gram');
            if (is_array($cached)) {
                return new MarketPrice($cached['pkr_per_gram'], $cached['source'], CarbonImmutable::parse($cached['observed_at']));
            }

            return $this->fetchFresh();
        });
    }

    private function fetchFresh(): MarketPrice
    {
        if (config('trading.price_mode') === 'demo') {
            return $this->remember(new MarketPrice(config('trading.demo_price_pkr'), 'Demo market feed', CarbonImmutable::now()));
        }

        $errors = [];
        foreach ($this->providers as $index => $provider) {
            if (config('trading.price_mode') === 'primary_down' && $index === 0) {
                continue;
            }
            try {
                return $this->remember($provider->fetch());
            } catch (Throwable $error) {
                $errors[] = $provider->name().': '.$error->getMessage();
            }
        }
        throw new PricingUnavailable('Live pricing is temporarily unavailable. Trading is paused. '.implode(' ', $errors));
    }

    private function remember(MarketPrice $price): MarketPrice
    {
        Cache::put('market-price:24k-pkr-gram', $price->toArray(), config('trading.price_cache_seconds'));
        return $price;
    }
}
