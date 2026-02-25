<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', ProductController::class);
Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
