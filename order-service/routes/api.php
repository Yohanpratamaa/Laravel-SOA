<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/user/{userId}', [OrderController::class, 'getUserOrders']);
Route::get('/orders/product/{productId}', [OrderController::class, 'getProductOrders']);
Route::post('/create-orders', [OrderController::class, 'store']);
Route::put('/update-orders/{id}', [OrderController::class, 'update']);
Route::delete('/delete-orders/{id}', [OrderController::class, 'destroy']);
