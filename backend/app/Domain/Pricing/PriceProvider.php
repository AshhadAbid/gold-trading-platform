<?php

namespace App\Domain\Pricing;

interface PriceProvider
{
    public function name(): string;
    public function fetch(): MarketPrice;
}
