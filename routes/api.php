<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{sku}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
