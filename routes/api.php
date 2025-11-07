<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SnapshotController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);

    Route::group(['prefix' => 'categories'], function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{slug}', [CategoryController::class, 'get']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'delete']);
    });

    Route::group(['prefix' => 'transactions'], function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);

        Route::get('/first', [TransactionController::class, 'first']);
        Route::get('/total', [TransactionController::class, 'total']);
        Route::get('/current-month', [TransactionController::class, 'getCurrentMonth']);
        Route::get('/monthly', [TransactionController::class, 'getMonthly']);
        Route::get('/month/{year}-{month}', [TransactionController::class, 'getByMonth']);
        Route::get('/category/{slug}', [TransactionController::class, 'getByCategory']);

        Route::get('/{transaction}', [TransactionController::class, 'get']);
        Route::put('/{transaction}', [TransactionController::class, 'update']);
        Route::delete('/{transaction}', [TransactionController::class, 'delete']);
    });

    Route::group(['prefix' => 'snapshots'], function () {
        Route::get('/', [SnapshotController::class, 'index']);
        Route::post('/', [SnapshotController::class, 'store']);
        Route::get('/latest', [SnapshotController::class, 'latest']);
        Route::get('/{snapshot}', [SnapshotController::class, 'get']);
        Route::put('/{snapshot}', [SnapshotController::class, 'update']);
        Route::delete('/{snapshot}', [SnapshotController::class, 'delete']);
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'get']);
    });
});
