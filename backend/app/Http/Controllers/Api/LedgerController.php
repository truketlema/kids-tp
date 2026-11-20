<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commitment;
use App\Models\Ledger;
use App\Services\FamilyNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LedgerController extends Controller
{
    private const TYPES = ['earning', 'deduction', 'bonus', 'adjustment'];

    public function childIndex(Request $request, int $childId): JsonResponse
    {
        $family = $request->user()->family;
        $child = $family?->children()->find($childId);

        if (! $family || ! $child) {
            return response()->json([
                'message' => 'This child was not found in your family.',
            ], $family ? 404 : 409);
        }

        $ledger = $child->ledgers()
            ->with(['commitment.category', 'recordedBy:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $weekLedger = $child->ledgers()
            ->where('created_at', '>=', now()->startOfWeek())
            ->get(['type', 'tp_amount']);

        return response()->json([
            'child' => $child->only(['id', 'name', 'date_of_birth', 'avatar', 'monthly_savings_goal']),
            'summary' => [
                'current_balance' => (int) $child->ledgers()->sum('tp_amount'),
                'earned_this_week' => (int) $weekLedger
                    ->whereIn('type', ['earning', 'bonus'])
                    ->where('tp_amount', '>', 0)
                    ->sum('tp_amount'),
                'earned_this_month' => (int) $child->ledgers()
                    ->whereIn('type', ['earning', 'bonus'])
                    ->where('tp_amount', '>', 0)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->sum('tp_amount'),
                'deducted_this_week' => abs((int) $weekLedger
                    ->where('type', 'deduction')
                    ->sum('tp_amount')),
                'deducted_today' => abs((int) $child->ledgers()
                    ->where('type', 'deduction')
                    ->where('created_at', '>=', now()->startOfDay())
                    ->sum('tp_amount')),
                'monthly_savings_goal' => (int) $child->monthly_savings_goal,
            ],
            'ledger' => $ledger->map(fn (Ledger $entry) => $this->serializeEntry($entry)),
        ]);
    }

    public function familyIndex(Request $request): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'required', 'string', Rule::in(self::TYPES)],
            'child_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('children', 'id')->where('family_id', $family->id),
            ],
        ]);

        $entries = Ledger::query()
            ->whereHas('child', fn ($query) => $query->where('family_id', $family->id))
            ->when(
                isset($validated['type']),
                fn ($query) => $query->where('type', $validated['type']),
            )
            ->when(
                isset($validated['child_id']),
                fn ($query) => $query->where('child_id', $validated['child_id']),
            )
            ->with(['child:id,name,avatar', 'commitment.category', 'recordedBy:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'ledger' => $entries->map(fn (Ledger $entry) => $this->serializeEntry($entry)),
        ]);
    }

    public function store(Request $request, int $childId): JsonResponse
    {
        return DB::transaction(function () use ($request, $childId) {
            $family = $request->user()->family;
            $child = $family?->children()->whereKey($childId)->lockForUpdate()->first();

            if (! $family || ! $child) {
                return response()->json([
                    'message' => 'This child was not found in your family.',
                ], $family ? 404 : 409);
            }

            $validated = $request->validate([
                'type' => ['required', 'string', Rule::in(self::TYPES)],
                'tp_amount' => [
                    Rule::requiredIf(fn () => in_array($request->input('type'), ['bonus', 'adjustment'], true)),
                    'integer',
                    'between:-2147483647,2147483647',
                ],
                'commitment_id' => [
                    Rule::requiredIf(fn () => in_array($request->input('type'), ['earning', 'deduction'], true)),
                    'integer',
                    'nullable',
                ],
                'remarks' => ['nullable', 'string', 'max:2000'],
            ]);

            $type = $validated['type'];
            $amount = (int) ($validated['tp_amount'] ?? 0);
            $commitment = null;

            if (in_array($type, ['earning', 'deduction'], true)) {
                $commitment = Commitment::query()
                    ->whereKey($validated['commitment_id'])
                    ->where(function ($query) use ($family) {
                        $query->whereNull('family_id')
                            ->orWhere('family_id', $family->id);
                    })
                    ->whereHas('category', function ($query) use ($family) {
                        $query->whereNull('family_id')
                            ->orWhere('family_id', $family->id);
                    })
                    ->first();

                if (! $commitment) {
                    return response()->json([
                        'message' => 'This activity was not found in your family.',
                    ], 404);
                }

                $childAssignment = $commitment->children()
                    ->whereKey($child->id)
                    ->first();

                if (! $childAssignment) {
                    return response()->json([
                        'message' => 'This activity is not assigned to this child.',
                    ], 422);
                }

                $defaultReward = (int) (
                    $childAssignment?->pivot->tp_override ?? $commitment->default_tp
                );
                $amount = (int) ($validated['tp_amount'] ?? (
                    $type === 'earning' ? $defaultReward : $commitment->suggested_deduction
                ));

                if ($amount < 1) {
                    return response()->json([
                        'message' => $type === 'earning'
                            ? 'The award amount must be a positive number of TP.'
                            : 'This activity has no suggested deduction. Enter a deduction amount.',
                    ], 422);
                }
            }

            if ($type === 'deduction') {
                if ($amount < 1) {
                    return response()->json([
                        'message' => 'A deduction must be a positive number of TP.',
                    ], 422);
                }

                $dailyDeductions = abs((int) $child->ledgers()
                    ->where('type', 'deduction')
                    ->where('created_at', '>=', now()->startOfDay())
                    ->sum('tp_amount'));
                $deductionCap = (int) $family->daily_deduction_cap;

                if ($dailyDeductions + $amount > $deductionCap) {
                    return response()->json([
                        'message' => "This deduction exceeds the family's daily limit of {$deductionCap} TP.",
                    ], 422);
                }

                $amount *= -1;
            } elseif ($type === 'bonus' && $amount < 1) {
                return response()->json([
                    'message' => 'A bonus must be a positive number of TP.',
                ], 422);
            }

            if ($type === 'adjustment' && trim((string) ($validated['remarks'] ?? '')) === '') {
                return response()->json([
                    'message' => 'Add a reason for this point adjustment.',
                ], 422);
            }

            $entry = DB::transaction(function () use ($amount, $child, $commitment, $family, $request, $type, $validated) {
                return $child->ledgers()->create([
                    'recorded_by' => $request->user()->id,
                    'commitment_id' => $commitment?->id,
                    'type' => $type,
                    'tp_amount' => $amount,
                    'conversion_rate_snapshot' => $family->conversion_rate,
                    'remarks' => $validated['remarks'] ?? null,
                ]);
            });

            $entry->load(['child:id,name,avatar', 'commitment.category', 'recordedBy:id,name']);
            app(FamilyNotificationService::class)->notifyOtherParents(
                $family,
                $request->user(),
                'points_recorded',
                'Points recorded for '.$child->name,
                $request->user()->name.' recorded '.($amount > 0 ? '+' : '').$amount.' TP.',
                ['child_id' => $child->id, 'ledger_id' => $entry->id, 'type' => $type],
            );

            return response()->json([
                'message' => 'Point activity recorded successfully.',
                'ledger' => $this->serializeEntry($entry),
                'current_balance' => (int) $child->ledgers()->sum('tp_amount'),
            ], 201);
        });
    }

    private function serializeEntry(Ledger $entry): array
    {
        return [
            'id' => $entry->id,
            'child_id' => $entry->child_id,
            'child_name' => $entry->child?->name,
            'child_avatar' => $entry->child?->avatar,
            'type' => $entry->type,
            'tp_amount' => (int) $entry->tp_amount,
            'conversion_rate_snapshot' => (float) $entry->conversion_rate_snapshot,
            'remarks' => $entry->remarks,
            'created_at' => $entry->created_at?->toISOString(),
            'recorded_by' => $entry->recordedBy?->name,
            'activity' => $entry->commitment ? [
                'title' => $entry->commitment->title,
                'category' => $entry->commitment->category?->name,
            ] : null,
        ];
    }
}
