<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WalletController;

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

    // Booking routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
});

Route::middleware(['auth:sanctum', 'role:admin|manager'])->group(function () {
    Route::patch('/bookings/{booking}', [BookingController::class, 'update']);
});
// coupon routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/coupons', [CouponController::class, 'index']);
    Route::post('/coupons', [CouponController::class, 'store']);
    Route::get('/coupons/{coupon}', [CouponController::class, 'show']);
    Route::put('/coupons/{coupon}', [CouponController::class, 'update']);
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy']);
});
// review routes
Route::get('/hotels/{hotel}/reviews', [ReviewController::class, 'index']);
//  
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
});
// 
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
});
//wallet routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
});