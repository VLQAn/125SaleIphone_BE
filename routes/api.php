<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Container\Attributes\Auth;
use App\Http\Controllers\ProfileController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('verify-email', [AuthController::class, 'verifyEmail']);
Route::post('resend-code', [AuthController::class, 'resendCode']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('user', [AuthController::class, 'getProfile']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Payment Callback (Public - for payment gateway callbacks)
Route::any('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback']);
Route::post('/payment/send-mail', [\App\Http\Controllers\PaymentController::class, 'sendMail']);

// Product Routes
Route::controller(ProductController::class)->group(function () {
    Route::get('products', 'index');
    Route::post('products', 'store');
    Route::get('products/{id}', 'show');
    Route::put('products/{id}', 'update');
    Route::delete('products/{id}', 'delete');
});

// Protected Routes - Require Authentication
Route::middleware('auth:sanctum')->group(function () {

    // Checkout & Order Routes
    Route::post('/payment/checkout', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::post('orders/cancel/{id}', [\App\Http\Controllers\Api\OrderController::class, 'huyDonHang']);
    Route::get('orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'chiTiet']);

    // Cart Routes
    Route::controller(\App\Http\Controllers\Api\CartController::class)->group(function () {
        Route::get('cart', 'index');
        Route::post('cart/add', 'addToCart');
        Route::put('cart/update', 'updateCart');
        Route::delete('cart/remove/{id}', 'removeFromCart');
    });

    Route::middleware('auth:sanctum')
    ->controller(ProfileController::class)
    ->group(function () {
        Route::get('profile', 'index');
        Route::put('profile', 'update');
        Route::put('profile/change-password', 'changePassword');
    });
});

