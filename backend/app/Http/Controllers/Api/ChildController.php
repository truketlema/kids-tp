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
                ->get(['id', 'name', 'date_of_birth', 'avatar', 'budget_allocation']),
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

    public function updateAllocation(Request $request, int $childId): JsonResponse
    {
        $family = $request->user()->family;

        if (!$family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $child = $family->children()->find($childId);

        if (!$child) {
            return response()->json([
                'message' => 'This child was not found in your family.',
            ], 404);
        }

        if ($request->exists('allocation') && $request->input('allocation') === null) {
            $child->budget_allocation = null;
            $child->save();

            return response()->json([
                'message' => 'The fixed budget allocation was restored.',
                'allocation' => null,
            ]);
        }

        $validated = $request->validate([
            'allocation' => [
                'required',
                'array:long_term_savings,planned_purchase,discretionary_spending,reserve',
            ],
            'allocation.long_term_savings' => ['required', 'integer', 'between:0,100'],
            'allocation.planned_purchase' => ['required', 'integer', 'between:0,100'],
            'allocation.discretionary_spending' => ['required', 'integer', 'between:0,100'],
            'allocation.reserve' => ['required', 'integer', 'between:0,100'],
        ]);

        if (array_sum($validated['allocation']) !== 100) {
            return response()->json([
                'message' => 'The allocation percentages must total 100%.',
            ], 422);
        }

        $child->budget_allocation = $validated['allocation'];
        $child->save();

        return response()->json([
            'message' => 'Budget allocation saved successfully.',
            'allocation' => $child->budget_allocation,
        ]);
    }
}
