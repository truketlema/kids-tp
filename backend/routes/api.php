<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\FamilyInvitationController;
use App\Http\Controllers\Api\LedgerController;
use App\Http\Controllers\Api\NotificationController;
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
    Route::patch('/user/profile', [AuthController::class, 'updateProfile'])
        ->middleware('throttle:5,1');
    Route::post('/user/email/verify', [AuthController::class, 'verifyEmailChange'])
        ->middleware('throttle:5,1');
    Route::post('/user/password', [AuthController::class, 'changePassword'])
        ->middleware('throttle:5,1');
    Route::get('/user/notification-preferences', [AuthController::class, 'notificationPreferences']);
    Route::patch('/user/notification-preferences', [AuthController::class, 'updateNotificationPreferences']);
    Route::delete('/user', [AuthController::class, 'destroyAccount'])
        ->middleware('throttle:5,1');
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markRead']);

    Route::patch('/family', [FamilyController::class, 'update']);
    Route::post('/family/invitations', [FamilyInvitationController::class, 'store']);
    Route::get('/ledger', [LedgerController::class, 'familyIndex']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::delete('/categories/{categoryId}', [CategoryController::class, 'destroyCategory'])
        ->whereNumber('categoryId');
    Route::post('/categories/{categoryId}/activities', [CategoryController::class, 'storeActivity'])
        ->whereNumber('categoryId');
    Route::patch('/activities/{activityId}', [CategoryController::class, 'updateActivity'])
        ->whereNumber('activityId');
    Route::post('/children/{childId}/activities/{activityId}', [CategoryController::class, 'assignChildActivity'])
        ->whereNumber('childId')
        ->whereNumber('activityId');
    Route::patch('/children/{childId}/activities/{activityId}', [CategoryController::class, 'updateChildTp'])
        ->whereNumber('childId')
        ->whereNumber('activityId');
    Route::delete('/activities/{activityId}', [CategoryController::class, 'destroyActivity'])
        ->whereNumber('activityId');
    Route::get('/children', [ChildController::class, 'index']);
    Route::post('/children', [ChildController::class, 'store']);
    Route::get('/children/{childId}/ledger', [LedgerController::class, 'childIndex'])
        ->whereNumber('childId');
    Route::post('/children/{childId}/ledger', [LedgerController::class, 'store'])
        ->whereNumber('childId');
    Route::patch('/children/{childId}', [ChildController::class, 'update'])->whereNumber('childId');
    Route::delete('/children/{childId}', [ChildController::class, 'destroy'])->whereNumber('childId');
    Route::patch('/children/{childId}/allocation', [ChildController::class, 'updateAllocation'])
        ->whereNumber('childId');
});
