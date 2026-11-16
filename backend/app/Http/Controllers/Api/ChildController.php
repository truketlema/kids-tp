<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChildController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $family = $request->user()->family;

        if (!$family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        return response()->json([
            'children' => $family->children()
                ->orderBy('name')
                ->get(['id', 'name', 'date_of_birth', 'avatar']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'avatar' => ['nullable', 'string', Rule::in(Child::AVATARS)],
        ]);

        $family = $request->user()->family;

        if (!$family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $child = $family->children()->create($validated);

        return response()->json([
            'message' => 'Child added successfully.',
            'child' => $child,
        ], 201);
    }
}
