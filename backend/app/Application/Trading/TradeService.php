<?php

namespace App\Application\Trading;

use App\Application\Pricing\MarketPriceService;
use App\Domain\Trading\TradeRejected;
use App\Domain\Trading\TradeSide;
use App\Models\Portfolio;
use App\Models\Trade;
use App\Models\TradeQuote;
use Illuminate\Support\Facades\DB;

final class TradeService
{
    public function __construct(private readonly MarketPriceService $prices) {}

    public function createQuote(TradeSide $side, float $grams): TradeQuote
    {
        $portfolio = Portfolio::where('owner', 'demo')->firstOrFail();
        $market = $this->prices->current();
        $unitPrice = $side === TradeSide::Buy
            ? max($market->pkrPerGram * config('trading.buy_markup'), config('trading.buy_guardrail_pkr'))
            : $market->pkrPerGram * config('trading.sell_multiplier');
        return TradeQuote::create([
            'portfolio_id' => $portfolio->id,
            'side' => $side->value,
            'gold_grams' => round($grams, 6),
            'market_price_pkr' => round($market->pkrPerGram, 2),
            'unit_price_pkr' => round($unitPrice, 2),
            'total_pkr' => round($unitPrice * $grams, 2),
            'price_source' => $market->source,
            'price_observed_at' => $market->observedAt,
            'expires_at' => now()->addSeconds(config('trading.quote_ttl_seconds')),
        ]);
    }

    public function confirm(TradeQuote $quote): Trade
    {
        return DB::transaction(function () use ($quote): Trade {
            $lockedQuote = TradeQuote::lockForUpdate()->findOrFail($quote->id);
            if ($lockedQuote->confirmed_at) {
                $existing = Trade::where('quote_id', $lockedQuote->id)->first();
                if ($existing) return $existing;
                throw new TradeRejected('This quote was already used.', 'already_confirmed', 409);
            }
            if ($lockedQuote->expires_at->isPast()) {
                throw new TradeRejected('This quote expired. Get a fresh price to continue.', 'quote_expired', 409);
            }
            $portfolio = Portfolio::lockForUpdate()->findOrFail($lockedQuote->portfolio_id);
            $grams = (float) $lockedQuote->gold_grams;
            $total = (float) $lockedQuote->total_pkr;
            if ($lockedQuote->side === TradeSide::Buy->value) {
                if ((float) $portfolio->cash_pkr < $total) throw new TradeRejected('Your PKR balance is too low for this purchase.', 'insufficient_cash');
                if ((float) $portfolio->platform_gold_grams < $grams) throw new TradeRejected('There is not enough platform gold inventory for this purchase.', 'insufficient_inventory');
                $portfolio->cash_pkr = (float) $portfolio->cash_pkr - $total;
                $portfolio->gold_grams = (float) $portfolio->gold_grams + $grams;
                $portfolio->platform_gold_grams = (float) $portfolio->platform_gold_grams - $grams;
            } else {
                if ((float) $portfolio->gold_grams < $grams) throw new TradeRejected('Your gold balance is too low for this sale.', 'insufficient_gold');
                $portfolio->cash_pkr = (float) $portfolio->cash_pkr + $total;
                $portfolio->gold_grams = (float) $portfolio->gold_grams - $grams;
                $portfolio->platform_gold_grams = (float) $portfolio->platform_gold_grams + $grams;
            }
            $portfolio->save();
            $trade = Trade::create([
                'quote_id'=>$lockedQuote->id,'portfolio_id'=>$portfolio->id,'side'=>$lockedQuote->side,
                'gold_grams'=>$lockedQuote->gold_grams,'unit_price_pkr'=>$lockedQuote->unit_price_pkr,'total_pkr'=>$lockedQuote->total_pkr,
                'cash_balance_pkr'=>$portfolio->cash_pkr,'gold_balance_grams'=>$portfolio->gold_grams,'platform_gold_balance_grams'=>$portfolio->platform_gold_grams,
            ]);
            $lockedQuote->update(['confirmed_at' => now()]);
            return $trade;
        }, 3);
    }
}
