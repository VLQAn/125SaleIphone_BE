<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Container\Attributes\Auth;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\GoogleController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('verify-email', [AuthController::class, 'verifyEmail']);
Route::post('resend-code', [AuthController::class, 'resendCode']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('user', [AuthController::class, 'getProfile']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('users/{idUser}', [AuthController::class, 'getUserById']);
    Route::put('users/{idUser}', [AuthController::class, 'updateUser']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Payment Callback (Public - for payment gateway callbacks)
Route::any('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback']);
Route::any('/payment/vnpay-callback', [\App\Http\Controllers\PaymentController::class, 'vnpayCallback']);
Route::post('/payment/send-mail', [\App\Http\Controllers\PaymentController::class, 'sendMail']);

// Product Routes
Route::controller(ProductController::class)->group(function () {
    Route::get('products', 'index');
    Route::post('products', 'store');
    Route::get('products/{id}', 'show');
    Route::get('products/{id}/edit', 'edit');
    Route::post('products/{idProduct}/variant/{idVariant}/update', 'updateVariant');
    Route::delete('products/{id}', 'destroy');
});

// Protected Routes - Require Authentication
Route::middleware('auth:sanctum')->group(function () {

     Route::controller(\App\Http\Controllers\OrderController::class)->group(function () {
        Route::post('checkout', 'checkout');
        Route::get('orders', 'index');
        Route::get('orders/{id}', 'show');
        Route::put('orders/{id}/cancel', 'cancel');
    });
    Route::middleware('auth:sanctum')->group(function () {
    // Order routes
    Route::controller(\App\Http\Controllers\OrderController::class)->group(function () {
        Route::post('checkout', 'checkout');
        Route::get('orders', 'index');
        Route::get('orders/{id}', 'show');
        Route::put('orders/{id}/cancel', 'cancel');
    });
});
    // Cart Routes
    Route::controller(\App\Http\Controllers\CartController::class)->group(function () {
        Route::get('cart', 'index');
        Route::post('cart/add', 'addToCart');
        Route::put('cart/update', 'updateCart');
        Route::delete('cart/remove/{id}', 'removeFromCart');
    });

    // Profile Routes
    Route::controller(ProfileController::class)->group(function () {
        Route::get('profile', 'index');
        Route::put('profile', 'update');
        Route::put('profile/change-password', 'changePassword');
    });
});

// Manager User
Route::get('/users', [UserController::class, 'index']);
Route::put('users/{idUser}/role', [UserController::class, 'updateRole']);

// Google OAuth Routes
Route::get('/auth/google', [GoogleController::class, 'redirect'])
    ->name('google.login');

Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
