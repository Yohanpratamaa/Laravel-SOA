<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/create-products', [ProductController::class, 'store']);
Route::put('/update-products/{id}', [ProductController::class, 'update']);
Route::delete('/delete-products/{id}', [ProductController::class, 'destroy']);
