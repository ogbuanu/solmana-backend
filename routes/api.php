<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');





Route::get('/test', [AuthController::class, 'test']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/request-email', [AuthController::class, 'requestEmail'])->middleware('auth:sanctum');
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user-details', [UserController::class, 'userDetails'])->middleware('auth:sanctum');



Route::post('/add-wallet-address', [UserController::class, 'addWalletAddress'])->middleware('auth:sanctum');
Route::get('/kyc-daily-update', [UserController::class, 'kycDailyUpdate']);
Route::post('/user-kyc', [UserController::class, 'userkyc']);


Route::post('/verify-tweet', [UserController::class, 'verifyTweet'])->middleware('auth:sanctum');


Route::get('/cleareverything', function () {
    $clearcache = Artisan::call('cache:clear');
    echo "Cache cleared<br>";

    $clearview = Artisan::call('view:clear');
    echo "View cleared<br>";

    $clearconfig = Artisan::call('config:cache');
    echo "Config cleared<br>";

    $cleardebugbar = Artisan::call('debugbar:clear');
    echo "Debug Bar cleared<br>";
});
