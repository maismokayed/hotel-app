<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\CityController;


//Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
});
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/users', [AuthController::class, 'index']);
});
//         hotel routes
Route::prefix('hotels')->group(function () {
    Route::get('/', [HotelController::class, 'index']);
    Route::get('/{hotel}', [HotelController::class, 'show']);
    Route::get('/{hotel}/reviews', [ReviewController::class, 'index']);
    Route::get('/{hotel}/images', [HotelController::class, 'getImages']);

    Route::middleware(['auth:sanctum', 'role:admin|manager'])->group(function () {
        Route::post('/', [HotelController::class, 'store']);
        Route::put('/{hotel}', [HotelController::class, 'update']);
        Route::patch('/{hotel}', [HotelController::class, 'update']);
        Route::delete('/{hotel}', [HotelController::class, 'destroy']);
        Route::post('/{hotel}/images', [HotelController::class, 'uploadImages']);
        Route::delete('/{hotel}/images/{mediaId}', [HotelController::class, 'deleteImage']);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])
        ->patch('/{hotel}/transfer', [HotelController::class, 'transfer']);
});

// Booking routes
Route::prefix('bookings')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [BookingController::class, 'index']);
    Route::post('/', [BookingController::class, 'store']);
    Route::get('/{booking}', [BookingController::class, 'show']);
    Route::patch('/{booking}/cancel', [BookingController::class, 'cancel']);

    Route::middleware('role:admin|manager')->group(function () {
        Route::patch('/{booking}', [BookingController::class, 'update']);
    });
});
// coupon routes
// coupon routes
Route::prefix('coupons')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/', [CouponController::class, 'index']);
    Route::post('/', [CouponController::class, 'store']);
    Route::get('/{coupon}', [CouponController::class, 'show']);
    Route::put('/{coupon}', [CouponController::class, 'update']);
    Route::delete('/{coupon}', [CouponController::class, 'destroy']);
});

// room routes
Route::prefix('rooms')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [RoomController::class, 'index']);
    Route::get('/{room}', [RoomController::class, 'show']);

    Route::middleware('role:admin|manager')->group(function () {
        Route::post('/', [RoomController::class, 'store']);
        Route::put('/{room}', [RoomController::class, 'update']);
        Route::delete('/{room}', [RoomController::class, 'destroy']);
    });
});
// review routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
});
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
});
//wallet routes
Route::prefix('wallet')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [WalletController::class, 'show']);
    Route::post('/deposit', [WalletController::class, 'deposit']);
    Route::get('/transactions', [WalletController::class, 'transactions']);
});
// admin Dashboard 
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
//cities
Route::get('/cities', [CityController::class, 'index']);
