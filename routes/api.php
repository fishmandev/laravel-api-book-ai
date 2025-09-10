<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
], function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Book CRUD routes - require authentication
    Route::middleware('auth:api')->group(function () {
        Route::apiResource('books', BookController::class);
    });
});
