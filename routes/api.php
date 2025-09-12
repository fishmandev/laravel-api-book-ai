<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
], function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Book CRUD routes - require authentication and permissions
    Route::middleware('auth:api')->group(function () {
        Route::get('/books', [BookController::class, 'index'])->middleware('permission:books.list');
        Route::post('/books', [BookController::class, 'store'])->middleware('permission:books.create');
        Route::get('/books/{book}', [BookController::class, 'show'])->middleware('permission:books.view');
        Route::put('/books/{book}', [BookController::class, 'update'])->middleware('permission:books.edit');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->middleware('permission:books.delete');
    });
});
