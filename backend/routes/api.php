<?php

use App\Http\Controllers\TradingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/portfolio', [TradingController::class, 'portfolio']);
    Route::get('/market-price', [TradingController::class, 'price']);
    Route::post('/quotes', [TradingController::class, 'quote']);
    Route::post('/quotes/{quote}/confirm', [TradingController::class, 'confirm']);
    Route::get('/trades', [TradingController::class, 'trades']);
});
