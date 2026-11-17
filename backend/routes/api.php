<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChildController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Kids TP API is running',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('family');
    });

    Route::get('/children', [ChildController::class, 'index']);
    Route::post('/children', [ChildController::class, 'store']);
    Route::patch('/children/{childId}/allocation', [ChildController::class, 'updateAllocation'])
        ->whereNumber('childId');
});