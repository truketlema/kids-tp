<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\FamilyInvitationMail;
use App\Models\FamilyInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FamilyInvitationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        if (User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'message' => 'An account already uses this email address.',
            ], 409);
        }

        $rawToken = Str::random(64);
        $expiresAt = now()->addDays(7);
        $inviterName = $request->user()->name;

        DB::transaction(function () use ($family, $validated, $rawToken, $expiresAt, $inviterName, $request) {
            FamilyInvitation::query()
                ->where('family_id', $family->id)
                ->where('email', $validated['email'])
                ->whereNull('accepted_at')
                ->delete();

            $invitation = FamilyInvitation::create([
                'family_id' => $family->id,
                'invited_by' => $request->user()->id,
                'email' => $validated['email'],
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => $expiresAt,
            ]);

            $inviteUrl = rtrim(config('services.kids_tp.frontend_url'), '/')
                .'/register?invitation='.urlencode($rawToken);

            Mail::to($invitation->email)->send(new FamilyInvitationMail(
                familyName: $family->family_name,
                inviterName: $inviterName,
                inviteUrl: $inviteUrl,
                expiresAt: $expiresAt,
            ));
        });

        return response()->json([
            'message' => 'Invitation email sent.',
            'expires_at' => $expiresAt->toISOString(),
        ], 201);
    }

    public function show(string $token): JsonResponse
    {
        $invitation = FamilyInvitation::query()
            ->with('family:id,family_name')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $invitation || $invitation->accepted_at || $invitation->expires_at->isPast()) {
            return response()->json([
                'message' => 'This invitation is invalid, expired, or already used.',
            ], 410);
        }

        return response()->json([
            'invitation' => [
                'email' => $invitation->email,
                'family_name' => $invitation->family->family_name,
                'expires_at' => $invitation->expires_at->toISOString(),
            ],
        ]);
    }

    public function accept(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $result = DB::transaction(function () use ($validated) {
            $invitation = FamilyInvitation::query()
                ->where('token_hash', hash('sha256', $validated['token']))
                ->lockForUpdate()
                ->first();

            if (! $invitation || $invitation->accepted_at || $invitation->expires_at->isPast()) {
                return null;
            }

            if (User::where('email', $invitation->email)->exists()) {
                return false;
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'family_id' => $invitation->family_id,
            ]);

            $invitation->accepted_at = now();
            $invitation->save();

            return [
                'user' => $user,
                'token' => $user->createToken('kids-tp')->plainTextToken,
            ];
        });

        if ($result === null) {
            return response()->json([
                'message' => 'This invitation is invalid, expired, or already used.',
            ], 410);
        }

        if ($result === false) {
            return response()->json([
                'message' => 'An account already uses this invitation email address.',
            ], 409);
        }

        return response()->json([
            'message' => 'You joined the family successfully.',
            'user' => $result['user']->load('family'),
            'token' => $result['token'],
        ], 201);
    }
}
