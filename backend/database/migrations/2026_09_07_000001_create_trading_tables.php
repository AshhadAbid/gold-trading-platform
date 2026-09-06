<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table): void {
            $table->id();
            $table->string('owner')->unique();
            $table->decimal('cash_pkr', 18, 2);
            $table->decimal('gold_grams', 18, 6);
            $table->decimal('platform_gold_grams', 18, 6);
            $table->timestamps();
        });
        Schema::create('trade_quotes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('side', 8);
            $table->decimal('gold_grams', 18, 6);
            $table->decimal('market_price_pkr', 18, 2);
            $table->decimal('unit_price_pkr', 18, 2);
            $table->decimal('total_pkr', 18, 2);
            $table->string('price_source');
            $table->timestampTz('price_observed_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('trades', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('quote_id')->unique()->constrained('trade_quotes');
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('side', 8);
            $table->decimal('gold_grams', 18, 6);
            $table->decimal('unit_price_pkr', 18, 2);
            $table->decimal('total_pkr', 18, 2);
            $table->decimal('cash_balance_pkr', 18, 2);
            $table->decimal('gold_balance_grams', 18, 6);
            $table->decimal('platform_gold_balance_grams', 18, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
        Schema::dropIfExists('trade_quotes');
        Schema::dropIfExists('portfolios');
    }
};
