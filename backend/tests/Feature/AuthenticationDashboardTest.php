<?php

namespace Tests\Feature;

use App\Mail\FamilyInvitationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_log_in_and_view_an_empty_children_list(): void
    {
        $this->registerParent();

        $login = $this->postJson('/api/login', [
            'name' => 'Selam Tesfaye',
            'email' => 'selam@example.com',
            'password' => 'password123',
        ])->assertOk();

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('name', 'Selam Tesfaye')
            ->assertJsonPath('family.family_name', 'Abebe family');

        $this->withToken($token)
            ->getJson('/api/children')
            ->assertOk()
            ->assertJsonCount(0, 'children');
    }

    public function test_parent_name_must_match_to_log_in(): void
    {
        $this->registerParent();

        $this->postJson('/api/login', [
            'name' => 'Someone Else',
            'email' => 'selam@example.com',
            'password' => 'password123',
        ])->assertUnauthorized();
    }

    public function test_parent_can_add_a_child_to_their_family(): void
    {
        $token = $this->registerParent();

        $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
                'avatar' => 'unknown',
            ])
            ->assertUnprocessable();

        $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
                'avatar' => 'cat',
            ])
            ->assertCreated()
            ->assertJsonPath('child.name', 'Hana Abebe')
            ->assertJsonPath('child.avatar', 'cat');

        $this->withToken($token)
            ->getJson('/api/children')
            ->assertOk()
            ->assertJsonCount(1, 'children')
            ->assertJsonPath('children.0.name', 'Hana Abebe')
            ->assertJsonPath('children.0.avatar', 'cat');
    }

    public function test_parent_can_view_default_activity_categories_and_values(): void
    {
        $token = $this->registerParent();

        $response = $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(7, 'categories')
            ->assertJsonPath('categories.0.name', 'Education')
            ->assertJsonPath('categories.0.is_default', true)
            ->assertJsonPath('categories.0.activities.0.title', 'Complete homework')
            ->assertJsonPath('categories.0.activities.0.is_default', true)
            ->assertJsonPath('categories.0.activities.0.default_tp', 3)
            ->assertJsonPath('categories.0.activities.0.suggested_deduction', 2)
            ->assertJsonPath('categories.5.name', 'Digital Responsibility')
            ->assertJsonPath('categories.6.name', 'Financial Literacy');

        $this->assertSame(
            34,
            collect($response->json('categories'))->sum(fn (array $category) => count($category['activities'])),
        );
    }

    public function test_category_order_keeps_defaults_fixed_and_custom_categories_in_creation_order(): void
    {
        $token = $this->registerParent();
        $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Family Projects',
                'icon' => 'mdi-palette-outline',
            ])
            ->assertCreated();
        $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Weekend Plans',
                'icon' => 'mdi-palette-outline',
            ])
            ->assertCreated();

        $categories = $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->json('categories');
        $education = collect($categories)->firstWhere('name', 'Education');
        $activity = collect($education['activities'])->firstWhere('title', 'Complete homework');

        $this->withToken($token)
            ->patchJson("/api/activities/{$activity['id']}", ['default_tp' => 8])
            ->assertOk();

        $orderedNames = $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(9, 'categories')
            ->json('categories');

        $this->assertSame([
            'Education',
            'Personal Care',
            'Household',
            'Trust & Character',
            'Time Management',
            'Digital Responsibility',
            'Financial Literacy',
            'Family Projects',
            'Weekend Plans',
        ], collect($orderedNames)->pluck('name')->all());
    }

    public function test_parent_can_add_categories_and_activities_without_exposing_them_to_other_families(): void
    {
        $token = $this->registerParent();
        $childId = $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
            ])
            ->assertCreated()
            ->json('child.id');
        $categoryId = $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Family Projects',
                'description' => 'Activities we choose together.',
                'icon' => 'mdi-palette-outline',
            ])
            ->assertCreated()
            ->assertJsonPath('category.name', 'Family Projects')
            ->json('category.id');

        $activityId = $this->withToken($token)
            ->postJson("/api/categories/{$categoryId}/activities", [
                'title' => 'Help plan a family trip',
                'description' => 'Compare options with a parent.',
                'default_tp' => 6,
                'suggested_deduction' => 1,
                'weight_multiplier' => 1,
                'child_ids' => [$childId],
            ])
            ->assertCreated()
            ->assertJsonPath('activity.default_tp', 6)
            ->assertJsonPath('activity.suggested_deduction', 1)
            ->json('activity.id');

        $this->withToken($token)
            ->patchJson("/api/activities/{$activityId}", [
                'title' => 'Plan a family trip',
                'description' => 'Compare travel options together.',
                'default_tp' => 7,
                'suggested_deduction' => 2,
                'weight_multiplier' => 1.5,
                'child_ids' => [$childId],
            ])
            ->assertOk()
            ->assertJsonPath('activity.title', 'Plan a family trip')
            ->assertJsonPath('activity.default_tp', 7)
            ->assertJsonPath('activity.suggested_deduction', 2);
        $this->assertDatabaseHas('commitments', [
            'id' => $activityId,
            'weight_multiplier' => 1.5,
        ]);
        $this->assertDatabaseHas('child_commitment', [
            'child_id' => $childId,
            'commitment_id' => $activityId,
        ]);

        $this->withToken($token)
            ->patchJson("/api/activities/{$activityId}", [])
            ->assertUnprocessable();

        $familyCategories = $this->withToken($token)->getJson('/api/categories')->assertOk();
        $customCategory = collect($familyCategories->json('categories'))
            ->firstWhere('name', 'Family Projects');
        $this->assertFalse($customCategory['activities'][0]['is_default']);

        $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Education',
                'icon' => 'mdi-book-open-variant',
            ])
            ->assertUnprocessable();

        $secondParent = $this->postJson('/api/register', [
            'name' => 'Mekdes Tadesse',
            'email' => 'mekdes@example.com',
            'phone' => '+251912345679',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'family_name' => 'Tadesse family',
        ])->assertCreated();

        $this->app['auth']->forgetGuards();
        $this->withToken($secondParent->json('token'))
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(7, 'categories');

        $this->app['auth']->forgetGuards();
        $this->withToken($secondParent->json('token'))
            ->postJson("/api/categories/{$categoryId}/activities", [
                'title' => 'Not allowed',
                'default_tp' => 5,
                'suggested_deduction' => 0,
                'weight_multiplier' => 1,
            ])
            ->assertNotFound();
    }

    public function test_family_can_delete_created_activities_and_categories_but_not_defaults_or_other_families_data(): void
    {
        $token = $this->registerParent();
        $categoryId = $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Weekend Activities',
                'icon' => 'mdi-sprout',
            ])
            ->assertCreated()
            ->json('category.id');
        $activityId = $this->withToken($token)
            ->postJson("/api/categories/{$categoryId}/activities", [
                'title' => 'Water plants',
                'default_tp' => 2,
                'weight_multiplier' => 1,
            ])
            ->assertCreated()
            ->json('activity.id');

        $categories = $this->withToken($token)->getJson('/api/categories')->assertOk();
        $this->assertFalse(
            collect($categories->json('categories'))->firstWhere('id', $categoryId)['is_default'],
        );
        $defaultCategory = collect($categories->json('categories'))->firstWhere('name', 'Education');
        $defaultActivityId = collect($defaultCategory['activities'])
            ->firstWhere('title', 'Complete homework')['id'];
        $this->withToken($token)
            ->deleteJson("/api/categories/{$defaultCategory['id']}")
            ->assertNotFound();
        $this->withToken($token)
            ->deleteJson("/api/activities/{$defaultActivityId}")
            ->assertNotFound();

        $secondParent = $this->postJson('/api/register', [
            'name' => 'Mekdes Tadesse',
            'email' => 'mekdes@example.com',
            'phone' => '+251912345679',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'family_name' => 'Tadesse family',
        ])->assertCreated();

        $this->app['auth']->forgetGuards();
        $this->withToken($secondParent->json('token'))
            ->deleteJson("/api/categories/{$categoryId}")
            ->assertNotFound();
        $this->withToken($secondParent->json('token'))
            ->deleteJson("/api/activities/{$activityId}")
            ->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)
            ->deleteJson("/api/activities/{$activityId}")
            ->assertOk();
        $this->assertDatabaseMissing('commitments', ['id' => $activityId]);

        $remainingActivityId = $this->withToken($token)
            ->postJson("/api/categories/{$categoryId}/activities", [
                'title' => 'Read together',
                'default_tp' => 3,
                'weight_multiplier' => 1,
            ])
            ->assertCreated()
            ->json('activity.id');

        $this->withToken($token)
            ->deleteJson("/api/categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('message', 'Category and its activities deleted successfully.');
        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
        $this->assertDatabaseMissing('commitments', ['id' => $remainingActivityId]);
    }

    public function test_created_activities_and_categories_with_point_history_cannot_be_deleted(): void
    {
        $token = $this->registerParent();
        $childId = $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
            ])
            ->assertCreated()
            ->json('child.id');
        $categoryId = $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Family Projects',
                'icon' => 'mdi-palette-outline',
            ])
            ->assertCreated()
            ->json('category.id');
        $activityId = $this->withToken($token)
            ->postJson("/api/categories/{$categoryId}/activities", [
                'title' => 'Build a garden box',
                'default_tp' => 5,
                'weight_multiplier' => 1,
            ])
            ->assertCreated()
            ->json('activity.id');

        DB::table('ledgers')->insert([
            'child_id' => $childId,
            'commitment_id' => $activityId,
            'recorded_by' => User::query()->where('email', 'selam@example.com')->value('id'),
            'type' => 'earned',
            'tp_amount' => 5,
            'conversion_rate_snapshot' => 1,
            'remarks' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)
            ->deleteJson("/api/activities/{$activityId}")
            ->assertConflict()
            ->assertJsonPath('message', 'This activity has point history and cannot be deleted.');
        $this->withToken($token)
            ->deleteJson("/api/categories/{$categoryId}")
            ->assertConflict()
            ->assertJsonPath('message', 'This category has activities with point history and cannot be deleted.');

        $this->assertDatabaseHas('categories', ['id' => $categoryId]);
        $this->assertDatabaseHas('commitments', ['id' => $activityId]);
    }

    public function test_editing_a_shared_activity_creates_a_family_only_override(): void
    {
        $token = $this->registerParent();
        $categories = $this->withToken($token)->getJson('/api/categories')->assertOk()->json('categories');
        $education = collect($categories)->firstWhere('name', 'Education');
        $activity = collect($education['activities'])->firstWhere('title', 'Complete homework');

        $this->withToken($token)
            ->patchJson("/api/activities/{$activity['id']}", [
                'title' => 'Finish homework',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Only the default TP reward can be updated for catalog activities.');

        $updateResponse = $this->withToken($token)
            ->patchJson("/api/activities/{$activity['id']}", ['default_tp' => 8])
            ->assertOk()
            ->assertJsonPath('activity.title', 'Complete homework')
            ->assertJsonPath('activity.default_tp', 8)
            ->assertJsonPath('activity.suggested_deduction', 2);
        $this->assertNotSame($education['id'], $updateResponse->json('category_id'));

        $familyCategories = $this->withToken($token)->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(7, 'categories');
        $familyEducation = collect($familyCategories->json('categories'))->firstWhere('name', 'Education');
        $this->assertTrue($familyEducation['is_default']);
        $this->assertSame(
            8,
            collect($familyEducation['activities'])->firstWhere('title', 'Complete homework')['default_tp'],
        );
        $familyActivityId = collect($familyEducation['activities'])
            ->firstWhere('title', 'Complete homework')['id'];
        $this->withToken($token)
            ->deleteJson("/api/categories/{$familyEducation['id']}")
            ->assertNotFound();
        $this->withToken($token)
            ->deleteJson("/api/activities/{$familyActivityId}")
            ->assertNotFound();
        $this->assertTrue(
            collect($familyEducation['activities'])
                ->firstWhere('title', 'Complete homework')['is_default'],
        );
        $this->assertDatabaseHas('commitments', [
            'id' => $activity['id'],
            'family_id' => null,
            'default_tp' => 3,
        ]);

        $secondParent = $this->postJson('/api/register', [
            'name' => 'Mekdes Tadesse',
            'email' => 'mekdes@example.com',
            'phone' => '+251912345679',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'family_name' => 'Tadesse family',
        ])->assertCreated();

        $this->app['auth']->forgetGuards();
        $this->withToken($secondParent->json('token'))
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('categories.0.activities.0.default_tp', 3)
            ->assertJsonPath('categories.0.activities.0.title', 'Complete homework');

        $this->app['auth']->forgetGuards();
        $this->withToken($secondParent->json('token'))
            ->patchJson("/api/activities/{$familyActivityId}", [
                'default_tp' => 4,
            ])
            ->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)
            ->patchJson("/api/activities/{$activity['id']}", ['default_tp' => 4294967296])
            ->assertUnprocessable();
    }

    public function test_parent_can_save_and_restore_a_child_budget_allocation(): void
    {
        $token = $this->registerParent();
        $childId = $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
            ])
            ->assertCreated()
            ->json('child.id');

        $this->withToken($token)
            ->getJson('/api/children')
            ->assertOk()
            ->assertJsonPath('children.0.budget_allocation', null);

        $allocation = [
            'long_term_savings' => 35,
            'planned_purchase' => 30,
            'discretionary_spending' => 20,
            'reserve' => 15,
        ];

        $this->withToken($token)
            ->patchJson("/api/children/{$childId}/allocation", ['allocation' => $allocation])
            ->assertOk()
            ->assertJsonPath('allocation.long_term_savings', 35);

        $this->withToken($token)
            ->getJson('/api/children')
            ->assertOk()
            ->assertJsonPath('children.0.budget_allocation.long_term_savings', 35);

        $invalidAllocation = [...$allocation, 'reserve' => 10];
        $this->withToken($token)
            ->patchJson("/api/children/{$childId}/allocation", ['allocation' => $invalidAllocation])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The allocation percentages must total 100%.');

        $this->withToken($token)
            ->patchJson("/api/children/{$childId}/allocation", ['allocation' => null])
            ->assertOk()
            ->assertJsonPath('allocation', null);

        $this->withToken($token)
            ->getJson('/api/children')
            ->assertOk()
            ->assertJsonPath('children.0.budget_allocation', null);
    }

    public function test_parent_cannot_update_another_familys_child_allocation(): void
    {
        $firstToken = $this->registerParent();
        $childId = $this->withToken($firstToken)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
            ])
            ->assertCreated()
            ->json('child.id');

        $secondParent = $this->postJson('/api/register', [
            'name' => 'Mekdes Tadesse',
            'email' => 'mekdes@example.com',
            'phone' => '+251912345679',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'family_name' => 'Tadesse family',
        ])->assertCreated();

        $secondToken = $secondParent->json('token');

        $this->app['auth']->forgetGuards();
        $this->withToken($secondToken)
            ->patchJson("/api/children/{$childId}/allocation", [
                'allocation' => [
                    'long_term_savings' => 40,
                    'planned_purchase' => 25,
                    'discretionary_spending' => 20,
                    'reserve' => 15,
                ],
            ])
            ->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->withToken($secondToken)
            ->patchJson("/api/children/{$childId}", ['name' => 'Not This Family'])
            ->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->withToken($secondToken)
            ->deleteJson("/api/children/{$childId}")
            ->assertNotFound();
    }

    public function test_parent_can_update_family_name_and_shared_financial_settings(): void
    {
        $token = $this->registerParent();

        $this->withToken($token)
            ->patchJson('/api/family', [
                'family_name' => 'Tesfaye Household',
                'conversion_rate' => 12.5,
                'daily_deduction_cap' => 8,
            ])
            ->assertOk()
            ->assertJsonPath('family.family_name', 'Tesfaye Household')
            ->assertJsonPath('family.conversion_rate', 12.5)
            ->assertJsonPath('family.daily_deduction_cap', 8);

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('family.family_name', 'Tesfaye Household')
            ->assertJsonPath('family.conversion_rate', 12.5)
            ->assertJsonPath('family.users.0.email', 'selam@example.com');
    }

    public function test_family_settings_reject_invalid_values_and_empty_updates(): void
    {
        $token = $this->registerParent();

        $this->withToken($token)
            ->patchJson('/api/family', ['conversion_rate' => 0])
            ->assertUnprocessable();

        $this->withToken($token)
            ->patchJson('/api/family', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Provide at least one family setting to update.');
    }

    public function test_parent_can_update_and_remove_a_child_without_ledger_history(): void
    {
        $token = $this->registerParent();
        $childId = $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
                'avatar' => 'cat',
            ])
            ->assertCreated()
            ->json('child.id');

        $this->withToken($token)
            ->patchJson("/api/children/{$childId}", [
                'name' => 'Hana Tesfaye',
                'date_of_birth' => '2014-06-21',
                'avatar' => 'star',
            ])
            ->assertOk()
            ->assertJsonPath('child.name', 'Hana Tesfaye')
            ->assertJsonPath('child.avatar', 'star');

        $this->withToken($token)
            ->patchJson("/api/children/{$childId}", [])
            ->assertUnprocessable();

        $this->withToken($token)
            ->deleteJson("/api/children/{$childId}")
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/children')
            ->assertOk()
            ->assertJsonCount(0, 'children');
    }

    public function test_parent_cannot_remove_a_child_with_ledger_history(): void
    {
        $token = $this->registerParent();
        $childId = $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
            ])
            ->assertCreated()
            ->json('child.id');
        $parentId = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->value('tokenable_id');

        DB::table('ledgers')->insert([
            'child_id' => $childId,
            'recorded_by' => $parentId,
            'type' => 'award',
            'tp_amount' => 5,
            'conversion_rate_snapshot' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)
            ->deleteJson("/api/children/{$childId}")
            ->assertConflict()
            ->assertJsonPath('message', 'This child has ledger history and cannot be removed.');
    }

    public function test_parent_invitation_can_be_accepted_once_and_adds_user_to_family(): void
    {
        Mail::fake();
        $token = $this->registerParent();

        $this->withToken($token)
            ->postJson('/api/family/invitations', ['email' => 'guardian@example.com'])
            ->assertCreated()
            ->assertJsonPath('message', 'Invitation email sent.');

        $invitationToken = null;
        Mail::assertSent(FamilyInvitationMail::class, function (FamilyInvitationMail $mail) use (&$invitationToken) {
            parse_str((string) parse_url($mail->inviteUrl, PHP_URL_QUERY), $query);
            $invitationToken = $query['invitation'] ?? null;

            return $mail->hasTo('guardian@example.com') && is_string($invitationToken);
        });

        $this->assertIsString($invitationToken);
        $this->getJson("/api/invitations/{$invitationToken}")
            ->assertOk()
            ->assertJsonPath('invitation.email', 'guardian@example.com')
            ->assertJsonPath('invitation.family_name', 'Abebe family');

        $this->postJson('/api/invitations/accept', [
            'token' => $invitationToken,
            'name' => 'Mimi Tesfaye',
            'phone' => '+251912345679',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonPath('user.name', 'Mimi Tesfaye')
            ->assertJsonPath('user.email', 'guardian@example.com')
            ->assertJsonPath('user.family.family_name', 'Abebe family');

        $this->getJson("/api/invitations/{$invitationToken}")->assertGone();
    }

    private function registerParent(): string
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Selam Tesfaye',
            'email' => 'selam@example.com',
            'phone' => '+251912345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'family_name' => 'Abebe family',
        ])->assertCreated();

        return $response->json('token');
    }
}
