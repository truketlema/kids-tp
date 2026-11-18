<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $validated = $request->validate([
            'family_name' => ['sometimes', 'required', 'string', 'max:255'],
            'conversion_rate' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'daily_deduction_cap' => ['sometimes', 'required', 'integer', 'between:0,1000000'],
        ]);

        if ($validated === []) {
            return response()->json([
                'message' => 'Provide at least one family setting to update.',
            ], 422);
        }

        $family->fill($validated)->save();

        return response()->json([
            'message' => 'Family settings updated successfully.',
            'family' => $family->fresh(),
        ]);
    }
}
