<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('user', [AuthController::class, 'getProfile']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/payment/checkout', [\App\Http\Controllers\PaymentController::class, 'checkout']);
Route::any('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback']);
Route::post('/payment/send-mail', [\App\Http\Controllers\PaymentController::class, 'sendMail']);