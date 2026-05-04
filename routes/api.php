<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;

//Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
});
//         hotel routes
Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/hotels/{hotel}', [HotelController::class, 'show']);

Route::middleware(['auth:sanctum','role:admin|manager'])->group(function () {
    //create hotel
    Route::post('/hotels', [HotelController::class, 'store']);
    //edit hotel
     Route::put('/hotels/{hotel}', [HotelController::class, 'update']);
    Route::patch('/hotels/{hotel}', [HotelController::class, 'update']);
//delete
    Route::delete('/hotels/{hotel}', [HotelController::class, 'destroy']);
});
Route::middleware(['auth:sanctum', 'role:admin'])
    ->patch('/hotels/{hotel}/transfer', [HotelController::class, 'transfer']);