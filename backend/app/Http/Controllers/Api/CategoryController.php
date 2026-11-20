<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Commitment;
use App\Models\Family;
use App\Services\FamilyNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    private const DEFAULT_CATEGORY_ORDER = [
        'Education',
        'Personal Care',
        'Household',
        'Trust & Character',
        'Time Management',
        'Digital Responsibility',
        'Financial Literacy',
    ];

    private const CATEGORY_ICONS = [
        'mdi-palette-outline',
        'mdi-book-open-variant',
        'mdi-school-outline',
        'mdi-emoticon-happy-outline',
        'mdi-music-note',
        'mdi-sprout',
        'mdi-run',
        'mdi-heart-outline',
        'mdi-home-outline',
        'mdi-lightbulb-outline',
        'mdi-star-outline',
        'mdi-account-group-outline',
    ];

    public function index(Request $request): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $categories = Category::query()
            ->where(function ($query) use ($family) {
                $query->where('family_id', $family->id)
                    ->orWhere(function ($globalQuery) use ($family) {
                        $globalQuery->whereNull('family_id')
                            ->whereNotIn(
                                'name',
                                Category::query()
                                    ->where('family_id', $family->id)
                                    ->select('name'),
                            );
                    });
            })
            ->with([
                'commitments' => function ($query) use ($family) {
                    $query->where(function ($commitmentQuery) use ($family) {
                        $commitmentQuery->whereNull('family_id')
                            ->orWhere('family_id', $family->id);
                    })
                        ->orderBy('id')
                        ->select([
                            'id',
                            'family_id',
                            'category_id',
                            'source_commitment_id',
                            'title',
                            'description',
                            'default_tp',
                            'suggested_deduction',
                            'weight_multiplier',
                        ]);
                },
                'commitments.children:id',
            ])
            ->orderBy('id')
            ->get(['id', 'family_id', 'name', 'description', 'icon']);

        $categories = $categories
            ->sort(function (Category $left, Category $right) {
                $leftDefaultOrder = array_search($left->name, self::DEFAULT_CATEGORY_ORDER, true);
                $rightDefaultOrder = array_search($right->name, self::DEFAULT_CATEGORY_ORDER, true);

                if ($leftDefaultOrder !== false && $rightDefaultOrder !== false) {
                    return $leftDefaultOrder <=> $rightDefaultOrder;
                }

                if ($leftDefaultOrder !== false) {
                    return -1;
                }

                if ($rightDefaultOrder !== false) {
                    return 1;
                }

                return $left->id <=> $right->id;
            })
            ->values();

        return response()->json([
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'icon' => $category->icon,
                'is_default' => in_array($category->name, self::DEFAULT_CATEGORY_ORDER, true),
                'activities' => $category->commitments->map(fn ($commitment) => [
                    'id' => $commitment->id,
                    'is_default' => $commitment->family_id === null || $commitment->source_commitment_id !== null,
                    'title' => $commitment->title,
                    'description' => $commitment->description,
                    'default_tp' => $commitment->default_tp,
                    'suggested_deduction' => $commitment->suggested_deduction,
                    'weight_multiplier' => $commitment->weight_multiplier,
                    'child_ids' => $commitment->children->pluck('id')->values(),
                    'child_tp_overrides' => $commitment->children
                        ->filter(fn ($child) => $child->pivot->tp_override !== null)
                        ->map(fn ($child) => [
                            'child_id' => $child->id,
                            'tp_override' => (int) $child->pivot->tp_override,
                        ])
                        ->values(),
                ])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:140'],
            'icon' => ['required', 'string', Rule::in(self::CATEGORY_ICONS)],
        ]);

        $nameIsTaken = Category::query()
            ->where('name', $validated['name'])
            ->where(function (Builder $query) use ($family) {
                $query->whereNull('family_id')
                    ->orWhere('family_id', $family->id);
            })
            ->exists();

        if ($nameIsTaken) {
            return response()->json([
                'message' => 'A category with this name already exists.',
            ], 422);
        }

        $category = $family->categories()->create($validated);

        return response()->json([
            'message' => 'Category added successfully.',
            'category' => $category,
        ], 201);
    }

    public function destroyCategory(Request $request, int $categoryId): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $result = DB::transaction(function () use ($family, $categoryId) {
            $category = $family->categories()
                ->whereNotIn('name', self::DEFAULT_CATEGORY_ORDER)
                ->lockForUpdate()
                ->find($categoryId);

            if (! $category) {
                return 'not_found';
            }

            $activities = $category->commitments()->lockForUpdate()->get();
            $hasHistory = $activities->contains(
                fn (Commitment $activity) => $activity->ledgers()->exists(),
            );

            if ($hasHistory) {
                return 'has_history';
            }

            $category->commitments()->delete();
            $category->delete();

            return 'deleted';
        });

        if ($result === 'not_found') {
            return response()->json([
                'message' => 'Only categories created by your family can be deleted.',
            ], 404);
        }

        if ($result === 'has_history') {
            return response()->json([
                'message' => 'This category has activities with point history and cannot be deleted.',
            ], 409);
        }

        return response()->json([
            'message' => 'Category and its activities deleted successfully.',
        ]);
    }

    public function storeActivity(Request $request, int $categoryId): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'default_tp' => ['required', 'integer', 'between:1,4294967295'],
            'suggested_deduction' => ['nullable', 'integer', 'between:0,4294967295'],
            'weight_multiplier' => ['required', 'numeric', 'between:0.1,10'],
            'child_ids' => ['sometimes', 'array'],
            'child_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('children', 'id')->where('family_id', $family->id),
            ],
        ]);

        $category = Category::query()
            ->where(function ($query) use ($family) {
                $query->whereNull('family_id')
                    ->orWhere('family_id', $family->id);
            })
            ->find($categoryId);

        if (! $category) {
            return response()->json([
                'message' => 'This category was not found.',
            ], 404);
        }

        $activity = DB::transaction(function () use ($category, $family, $validated) {
            $familyCategory = $this->familyCategory($category, $family);
            $childIds = $validated['child_ids'] ?? [];
            unset($validated['child_ids']);

            $activity = $familyCategory->commitments()->create([
                ...$validated,
                'family_id' => $family->id,
                'suggested_deduction' => $validated['suggested_deduction'] ?? 0,
            ]);

            $activity->children()->sync($childIds);

            return $activity;
        });

        return response()->json([
            'message' => 'Activity added successfully.',
            'activity' => $activity,
        ], 201);
    }

    public function updateActivity(Request $request, int $activityId): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $source = Commitment::query()->with('category')->find($activityId);

        if (! $source || ($source->family_id !== null && $source->family_id !== $family->id)) {
            return response()->json([
                'message' => 'This activity was not found in your family.',
            ], 404);
        }

        $isCatalogActivity = $source->family_id === null || $source->source_commitment_id !== null;
        $allowedFields = $isCatalogActivity
            ? ['child_ids']
            : ['title', 'description', 'suggested_deduction', 'weight_multiplier', 'child_ids'];
        $disallowedFields = array_diff(array_keys($request->all()), $allowedFields);

        if ($disallowedFields !== []) {
            $message = in_array('default_tp', $disallowedFields, true)
                ? 'TP rewards can only be updated individually for each child.'
                : ($isCatalogActivity
                    ? 'Catalog activity details cannot be changed here.'
                    : 'The request contains fields that cannot be updated.');

            return response()->json([
                'message' => $message,
            ], 422);
        }

        $rules = [
            'child_ids' => ['sometimes', 'array'],
            'child_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('children', 'id')->where('family_id', $family->id),
            ],
        ];

        if (! $isCatalogActivity) {
            $rules += [
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'suggested_deduction' => ['sometimes', 'required', 'integer', 'between:0,4294967295'],
                'weight_multiplier' => ['sometimes', 'required', 'numeric', 'between:0.1,10'],
            ];
        }

        $validated = $request->validate($rules);

        if ($validated === []) {
            return response()->json([
                'message' => 'Provide at least one activity field to update.',
            ], 422);
        }

        $activity = DB::transaction(function () use ($activityId, $family, $validated) {
            $source = Commitment::query()->with('category')->find($activityId);

            if (! $source) {
                return null;
            }

            $activity = $this->familyActivity($source, $family);

            if (! $activity) {
                return null;
            }

            $childIds = $validated['child_ids'] ?? null;
            unset($validated['child_ids']);
            $activity->fill($validated);

            $activity->save();

            if ($childIds !== null) {
                $activity->children()->sync($childIds);
            }

            return $activity->fresh();
        });

        if (! $activity) {
            return response()->json([
                'message' => 'This activity was not found in your family.',
            ], 404);
        }

        return response()->json([
            'message' => 'Activity updated successfully.',
            'activity' => $activity,
            'category_id' => $activity->category_id,
        ]);
    }

    public function assignChildActivity(Request $request, int $childId, int $activityId): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $child = $family->children()->find($childId);

        if (! $child) {
            return response()->json([
                'message' => 'This child was not found in your family.',
            ], 404);
        }

        $validated = $request->validate([
            'tp_override' => ['required', 'integer', 'between:1,4294967295'],
        ]);

        $result = DB::transaction(function () use ($activityId, $child, $family, $request, $validated) {
            $source = Commitment::query()
                ->with('category')
                ->lockForUpdate()
                ->find($activityId);

            if (! $source) {
                return null;
            }

            $activity = $this->familyActivity($source, $family);

            if (! $activity) {
                return null;
            }

            $wasAssigned = $child->commitments()->whereKey($activity->id)->exists();
            $child->commitments()->syncWithoutDetaching([
                $activity->id => ['tp_override' => $validated['tp_override']],
            ]);

            if (! $wasAssigned) {
                app(FamilyNotificationService::class)->notifyOtherParents(
                    $family,
                    $request->user(),
                    'activity_assignment_changed',
                    'A child activity was assigned',
                    $request->user()->name.' assigned '.$activity->title.' to '.$child->name.'.',
                    ['child_id' => $child->id, 'activity_id' => $activity->id],
                );
            }

            return ['activity' => $activity];
        });

        if (! $result) {
            return response()->json([
                'message' => 'This activity was not found in your family.',
            ], 404);
        }

        return response()->json([
            'message' => 'Activity assigned successfully.',
            'child_id' => $child->id,
            'activity_id' => $result['activity']->id,
            'tp_override' => (int) $validated['tp_override'],
        ], 201);
    }

    public function updateChildTp(Request $request, int $childId, int $activityId): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $child = $family->children()->find($childId);

        if (! $child) {
            return response()->json([
                'message' => 'This child was not found in your family.',
            ], 404);
        }

        $validated = $request->validate([
            'tp_override' => ['required', 'integer', 'between:1,4294967295'],
        ]);

        $activity = Commitment::query()
            ->where('family_id', $family->id)
            ->whereKey($activityId)
            ->whereHas('children', fn (Builder $query) => $query->whereKey($child->id))
            ->first();

        if (! $activity) {
            return response()->json([
                'message' => 'This activity is not assigned to this child in your family.',
            ], 404);
        }

        DB::transaction(function () use ($activity, $child, $family, $request, $validated) {
            $child->commitments()->updateExistingPivot($activity->id, [
                'tp_override' => $validated['tp_override'],
            ]);
            app(FamilyNotificationService::class)->notifyOtherParents(
                $family,
                $request->user(),
                'activity_assignment_changed',
                'A child activity reward changed',
                $request->user()->name.' updated the TP reward for '.$activity->title.' assigned to '.$child->name.'.',
                ['child_id' => $child->id, 'activity_id' => $activity->id],
            );
        });

        return response()->json([
            'message' => 'Child activity reward updated successfully.',
            'child_id' => $child->id,
            'activity_id' => $activity->id,
            'tp_override' => (int) $validated['tp_override'],
        ]);
    }

    public function destroyActivity(Request $request, int $activityId): JsonResponse
    {
        $family = $request->user()->family;

        if (! $family) {
            return response()->json([
                'message' => 'This account is not associated with a family.',
            ], 409);
        }

        $result = DB::transaction(function () use ($family, $activityId) {
            $activity = Commitment::query()
                ->where('family_id', $family->id)
                ->whereNull('source_commitment_id')
                ->lockForUpdate()
                ->find($activityId);

            if (! $activity) {
                return 'not_found';
            }

            if ($activity->ledgers()->exists()) {
                return 'has_history';
            }

            $activity->delete();

            return 'deleted';
        });

        if ($result === 'not_found') {
            return response()->json([
                'message' => 'Only activities created by your family can be deleted.',
            ], 404);
        }

        if ($result === 'has_history') {
            return response()->json([
                'message' => 'This activity has point history and cannot be deleted.',
            ], 409);
        }

        return response()->json([
            'message' => 'Activity deleted successfully.',
        ]);
    }

    private function familyCategory(Category $category, Family $family): Category
    {
        if ($category->family_id === $family->id) {
            return $category;
        }

        $familyCategory = $family->categories()
            ->where('name', $category->name)
            ->first();

        if ($familyCategory) {
            return $familyCategory;
        }

        $familyCategory = $family->categories()->create([
            'name' => $category->name,
            'description' => $category->description,
            'icon' => $category->icon,
        ]);

        $category->commitments()
            ->orderBy('id')
            ->get()
            ->each(function (Commitment $sourceActivity) use ($familyCategory, $family) {
                $familyCategory->commitments()->create([
                    'family_id' => $family->id,
                    'source_commitment_id' => $sourceActivity->id,
                    'title' => $sourceActivity->title,
                    'description' => $sourceActivity->description,
                    'weight_multiplier' => $sourceActivity->weight_multiplier,
                    'default_tp' => $sourceActivity->default_tp,
                    'suggested_deduction' => $sourceActivity->suggested_deduction,
                ]);
            });

        return $familyCategory;
    }

    private function familyActivity(Commitment $source, Family $family): ?Commitment
    {
        if ($source->family_id === $family->id) {
            return $source;
        }

        if ($source->family_id !== null || ! $source->category || $source->category->family_id !== null) {
            return null;
        }

        $familyCategory = $this->familyCategory($source->category, $family);

        return $familyCategory->commitments()
            ->where('source_commitment_id', $source->id)
            ->first()
            ?? $familyCategory->commitments()->create([
                'family_id' => $family->id,
                'source_commitment_id' => $source->id,
                'title' => $source->title,
                'description' => $source->description,
                'weight_multiplier' => $source->weight_multiplier,
                'default_tp' => $source->default_tp,
                'suggested_deduction' => $source->suggested_deduction,
            ]);
    }
}
