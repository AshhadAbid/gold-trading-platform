<?php

use Illuminate\Support\Facades\Route;

Route::get('/{path?}', function () {
    $frontend = public_path('app.html');

    if (is_file($frontend)) {
        return response()->file($frontend);
    }

    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
        'api' => url('/api/v1'),
    ]);
})->where('path', '^(?!api(?:/|$)|up$).*$');
