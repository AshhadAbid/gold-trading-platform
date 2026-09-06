<?php

namespace App\Http\Controllers;

use App\Application\Pricing\MarketPriceService;
use App\Application\Trading\TradeService;
use App\Domain\Pricing\PricingUnavailable;
use App\Domain\Trading\TradeRejected;
use App\Domain\Trading\TradeSide;
use App\Models\Portfolio;
use App\Models\Trade;
use App\Models\TradeQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TradingController extends Controller
{
    public function portfolio(): JsonResponse
    {
        return response()->json(['data' => Portfolio::where('owner', 'demo')->firstOrFail()]);
    }

    public function price(MarketPriceService $prices): JsonResponse
    {
        try {
            $market = $prices->current();
            return response()->json(['data' => array_merge($market->toArray(), [
                'customer_buy_pkr_per_gram' => round(max($market->pkrPerGram * config('trading.buy_markup'), config('trading.buy_guardrail_pkr')), 2),
                'customer_sell_pkr_per_gram' => round($market->pkrPerGram * config('trading.sell_multiplier'), 2),
                'buy_markup' => config('trading.buy_markup'),
                'sell_multiplier' => config('trading.sell_multiplier'),
                'buy_guardrail_pkr' => config('trading.buy_guardrail_pkr'),
            ])]);
        } catch (PricingUnavailable $error) {
            return response()->json(['message' => $error->getMessage(), 'reason' => 'pricing_unavailable'], 503);
        }
    }

    public function quote(Request $request, TradeService $trading): JsonResponse
    {
        $input = $request->validate([
            'side' => ['required', Rule::enum(TradeSide::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'amount_unit' => ['required', Rule::in(['pkr', 'gold'])],
        ]);
        try {
            $side = TradeSide::from($input['side']);
            $market = app(MarketPriceService::class)->current();
            $estimatedUnit = $side === TradeSide::Buy
                ? max($market->pkrPerGram * config('trading.buy_markup'), config('trading.buy_guardrail_pkr'))
                : $market->pkrPerGram * config('trading.sell_multiplier');
            $grams = $input['amount_unit'] === 'gold' ? (float) $input['amount'] : (float) $input['amount'] / $estimatedUnit;
            if ($grams < 0.000001) return response()->json(['message' => 'Enter at least 0.000001 gram.', 'reason' => 'amount_too_small'], 422);
            $quote = $trading->createQuote($side, $grams);
            return response()->json(['data' => $this->quoteData($quote)], 201);
        } catch (PricingUnavailable $error) {
            return response()->json(['message' => $error->getMessage(), 'reason' => 'pricing_unavailable'], 503);
        }
    }

    public function confirm(TradeQuote $quote, TradeService $trading): JsonResponse
    {
        try {
            $trade = $trading->confirm($quote);
            return response()->json(['data' => $trade]);
        } catch (TradeRejected $error) {
            return response()->json(['message' => $error->getMessage(), 'reason' => $error->reason], $error->status);
        }
    }

    public function trades(): JsonResponse
    {
        return response()->json(['data' => Trade::latest()->limit(10)->get()]);
    }

    private function quoteData(TradeQuote $quote): array
    {
        return array_merge($quote->toArray(), ['seconds_remaining' => max(0, now()->diffInSeconds($quote->expires_at, false))]);
    }
}
