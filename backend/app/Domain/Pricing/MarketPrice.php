<?php

namespace App\Domain\Pricing;

use Carbon\CarbonImmutable;

final readonly class MarketPrice
{
    public function __construct(
        public float $pkrPerGram,
        public string $source,
        public CarbonImmutable $observedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'pkr_per_gram' => round($this->pkrPerGram, 2),
            'source' => $this->source,
            'observed_at' => $this->observedAt->toIso8601String(),
        ];
    }
}
