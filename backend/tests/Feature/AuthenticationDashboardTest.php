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
