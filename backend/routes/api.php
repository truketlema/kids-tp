<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\FamilyInvitationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Kids TP API is running',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/invitations/{token}', [FamilyInvitationController::class, 'show']);
Route::post('/invitations/accept', [FamilyInvitationController::class, 'accept']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('family.users:id,family_id,name,email,phone');
    });

    Route::patch('/family', [FamilyController::class, 'update']);
    Route::post('/family/invitations', [FamilyInvitationController::class, 'store']);
    Route::get('/children', [ChildController::class, 'index']);
    Route::post('/children', [ChildController::class, 'store']);
    Route::patch('/children/{childId}', [ChildController::class, 'update'])->whereNumber('childId');
    Route::delete('/children/{childId}', [ChildController::class, 'destroy'])->whereNumber('childId');
    Route::patch('/children/{childId}/allocation', [ChildController::class, 'updateAllocation'])
        ->whereNumber('childId');
});
