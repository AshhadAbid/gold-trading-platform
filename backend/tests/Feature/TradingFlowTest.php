<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TradingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Portfolio::create(['owner'=>'demo','cash_pkr'=>2500000,'gold_grams'=>20,'platform_gold_grams'=>100]);
        Cache::put('market-price:24k-pkr-gram', ['pkr_per_gram'=>40000,'source'=>'Test feed','observed_at'=>now()->toIso8601String()], 300);
    }

    public function test_buy_is_atomic_and_repeated_confirmation_is_idempotent(): void
    {
        $quote = $this->postJson('/api/v1/quotes', ['side'=>'buy','amount'=>2,'amount_unit'=>'gold'])
            ->assertCreated()->json('data');
        $this->assertSame(45000.0, (float) $quote['unit_price_pkr']);

        $first = $this->postJson("/api/v1/quotes/{$quote['id']}/confirm")->assertOk()->json('data');
        $second = $this->postJson("/api/v1/quotes/{$quote['id']}/confirm")->assertOk()->json('data');

        $this->assertSame($first['id'], $second['id']);
        $this->assertDatabaseCount('trades', 1);
        $this->assertDatabaseHas('portfolios', ['owner'=>'demo','cash_pkr'=>'2410000.00','gold_grams'=>'22.000000','platform_gold_grams'=>'98.000000']);
    }

    public function test_expired_quote_cannot_trade(): void
    {
        $quote = $this->postJson('/api/v1/quotes', ['side'=>'sell','amount'=>1,'amount_unit'=>'gold'])->json('data');
        \App\Models\TradeQuote::whereKey($quote['id'])->update(['expires_at'=>now()->subSecond()]);
        $this->postJson("/api/v1/quotes/{$quote['id']}/confirm")
            ->assertStatus(409)->assertJsonPath('reason', 'quote_expired');
        $this->assertDatabaseCount('trades', 0);
    }

    public function test_insufficient_customer_gold_is_rejected_without_partial_update(): void
    {
        $quote = $this->postJson('/api/v1/quotes', ['side'=>'sell','amount'=>21,'amount_unit'=>'gold'])->json('data');
        $this->postJson("/api/v1/quotes/{$quote['id']}/confirm")
            ->assertStatus(422)->assertJsonPath('reason', 'insufficient_gold');
        $this->assertDatabaseHas('portfolios', ['owner'=>'demo','gold_grams'=>'20.000000']);
    }
}
