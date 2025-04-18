<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\UserController;

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::post('/create-users', [UserController::class, 'store']);
Route::put('/update-users/{id}', [UserController::class, 'update']);
Route::delete('/delete-users/{id}', [UserController::class, 'destroy']);
