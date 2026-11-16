<?php

use App\Http\Controllers\Api\Stock\StockMutationController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\MeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');


Route::middleware(['auth:api'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [MeController::class, 'index']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('stock-mutations', StockMutationController::class);
});