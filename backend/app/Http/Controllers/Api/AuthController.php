<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailChangeVerificationMail;
use App\Models\Family;
use App\Models\Ledger;
use App\Models\User;
use App\Services\FamilyNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    private const DEFAULT_NOTIFICATION_PREFERENCES = [
        'points_recorded' => true,
        'activity_assignment_changed' => true,
        'family_access_changed' => true,
    ];

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'family_name' => ['required', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($validated) {
            $family = Family::create([
                'family_name' => $validated['family_name'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'family_id' => $family->id,
            ]);

            $token = $user->createToken('kids-tp')->plainTextToken;

            return [
                'user' => $user,
                'family' => $family,
                'token' => $token,
            ];
        });

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $result['user'],
            'family' => $result['family'],
            'token' => $result['token'],
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (
            ! $user
            || $user->name !== $validated['name']
            || ! Hash::check($validated['password'], $user->password)
        ) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $token = $user->createToken('kids-tp')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        if ($validated['email'] !== $user->email) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->forceFill([
                'pending_email' => $validated['email'],
                'email_verification_code_hash' => Hash::make($code),
                'email_change_expires_at' => now()->addMinutes(15),
            ]);
        } else {
            $code = null;
            $user->forceFill([
                'pending_email' => null,
                'email_verification_code_hash' => null,
                'email_change_expires_at' => null,
            ]);
        }

        $user->save();

        if ($code !== null) {
            Mail::to($validated['email'])->send(new EmailChangeVerificationMail($code));
        }

        return response()->json([
            'message' => $code === null
                ? 'Personal information updated successfully.'
                : 'A verification code was sent to your new email address.',
            'user' => $user->fresh()->load('family.users:id,family_id,name,email,phone'),
            'pending_email' => $user->pending_email,
        ]);
    }

    public function verifyEmailChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $result = DB::transaction(function () use ($request, $validated) {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);

            if (
                ! $user->pending_email
                || ! $user->email_verification_code_hash
                || ! $user->email_change_expires_at
                || $user->email_change_expires_at->isPast()
            ) {
                return ['error' => 'The email verification code is missing or expired. Request a new code.'];
            }

            if (! Hash::check($validated['code'], $user->email_verification_code_hash)) {
                return ['error' => 'The email verification code is incorrect.'];
            }

            if (User::query()->where('email', $user->pending_email)->where('id', '!=', $user->id)->exists()) {
                return ['error' => 'This email address is already associated with another account.'];
            }

            $user->email = $user->pending_email;
            $user->email_verified_at = now();
            $user->forceFill([
                'pending_email' => null,
                'email_verification_code_hash' => null,
                'email_change_expires_at' => null,
            ]);
            $user->save();

            return ['user' => $user->fresh()->load('family.users:id,family_id,name,email,phone')];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }

        return response()->json([
            'message' => 'Email address updated successfully.',
            'user' => $result['user'],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
            ], 422);
        }

        $user->password = $validated['password'];
        $user->save();
        $currentTokenId = $user->currentAccessToken()?->getKey();
        $user->tokens()
            ->when(
                $currentTokenId,
                fn ($query) => $query->where('id', '!=', $currentTokenId),
                fn ($query) => $query,
            )
            ->delete();

        return response()->json([
            'message' => 'Password changed successfully. Other active sessions were signed out.',
        ]);
    }

    public function notificationPreferences(Request $request): JsonResponse
    {
        return response()->json([
            'preferences' => array_replace(
                self::DEFAULT_NOTIFICATION_PREFERENCES,
                $request->user()->notification_preferences ?? [],
            ),
        ]);
    }

    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'points_recorded' => ['sometimes', 'required', 'boolean'],
            'activity_assignment_changed' => ['sometimes', 'required', 'boolean'],
            'family_access_changed' => ['sometimes', 'required', 'boolean'],
        ]);

        if ($validated === []) {
            return response()->json(['message' => 'Choose at least one notification preference to update.'], 422);
        }

        $user = $request->user();
        $user->notification_preferences = array_replace(
            self::DEFAULT_NOTIFICATION_PREFERENCES,
            $user->notification_preferences ?? [],
            $validated,
        );
        $user->save();

        return response()->json([
            'message' => 'Notification preferences updated.',
            'preferences' => $user->notification_preferences,
        ]);
    }

    public function destroyAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The password is incorrect.',
            ], 422);
        }

        $result = DB::transaction(function () use ($user) {
            $family = $user->family_id
                ? Family::query()->lockForUpdate()->find($user->family_id)
                : null;
            $otherParents = $family
                ? $family->users()->where('id', '!=', $user->id)->lockForUpdate()->get()
                : collect();
            $hasOtherParents = $otherParents->isNotEmpty();

            $user->tokens()->delete();
            $user->notifications()->delete();
            DB::table('password_reset_tokens')
                ->whereIn('email', array_filter([$user->email, $user->pending_email]))
                ->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            if ($family && $hasOtherParents) {
                Ledger::query()
                    ->where('recorded_by', $user->id)
                    ->update(['recorded_by' => null]);
                app(FamilyNotificationService::class)->notifyOtherParents(
                    $family,
                    $user,
                    'family_access_changed',
                    'A parent left your family',
                    'A parent account was removed. Your shared family records are still available.',
                );
                $user->delete();

                return ['family_deleted' => false];
            }

            if ($family) {
                $childIds = DB::table('children')
                    ->where('family_id', $family->id)
                    ->pluck('id');

                DB::table('ledgers')->whereIn('child_id', $childIds)->delete();
                DB::table('commitments')->where('family_id', $family->id)->delete();
                DB::table('children')->where('family_id', $family->id)->delete();
                DB::table('categories')->where('family_id', $family->id)->delete();
                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->whereIn('notifiable_id', $family->users()->pluck('id'))
                    ->delete();
            }

            $user->delete();
            $family?->delete();

            return ['family_deleted' => (bool) $family];
        });

        return response()->json([
            'message' => $result['family_deleted']
                ? 'The account and family data were deleted.'
                : 'The account was deleted. Shared family records were preserved.',
            'family_deleted' => $result['family_deleted'],
        ]);
    }
}
