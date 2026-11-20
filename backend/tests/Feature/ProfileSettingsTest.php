<?php

namespace Tests\Feature;

use App\Mail\EmailChangeVerificationMail;
use App\Models\User;
use App\Notifications\FamilyEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_update_personal_details_and_verify_a_new_email(): void
    {
        Mail::fake();
        $token = $this->registerParent();

        $this->withToken($token)
            ->patchJson('/api/user/profile', [
                'name' => 'Selam New',
                'phone' => '+251912345679',
                'email' => 'selam.new@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Selam New')
            ->assertJsonPath('user.phone', '+251912345679')
            ->assertJsonPath('user.email', 'selam@example.com')
            ->assertJsonPath('pending_email', 'selam.new@example.com');

        $code = null;
        Mail::assertSent(EmailChangeVerificationMail::class, function (EmailChangeVerificationMail $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo('selam.new@example.com');
        });

        $this->withToken($token)
            ->postJson('/api/user/email/verify', ['code' => '000000'])
            ->assertUnprocessable();

        $this->assertIsString($code);
        $this->withToken($token)
            ->postJson('/api/user/email/verify', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('user.email', 'selam.new@example.com')
            ->assertJsonPath('user.pending_email', null);

        $this->assertDatabaseHas('users', [
            'email' => 'selam.new@example.com',
            'phone' => '+251912345679',
        ]);
    }

    public function test_parent_can_change_password_after_providing_the_current_password(): void
    {
        $token = $this->registerParent();

        $this->withToken($token)
            ->postJson('/api/user/password', [
                'current_password' => 'incorrect',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertUnprocessable();

        $this->withToken($token)
            ->postJson('/api/user/password', [
                'current_password' => 'password123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk();

        $this->postJson('/api/login', [
            'name' => 'Selam Tesfaye',
            'email' => 'selam@example.com',
            'password' => 'newpassword123',
        ])->assertOk();
    }

    public function test_parent_can_remove_their_account_while_preserving_shared_family_records(): void
    {
        $token = $this->registerParent();
        $parent = User::query()->where('email', 'selam@example.com')->firstOrFail();
        $otherParent = User::create([
            'name' => 'Mimi Tesfaye',
            'email' => 'mimi@example.com',
            'phone' => '+251912345680',
            'password' => 'password123',
            'family_id' => $parent->family_id,
        ]);
        $childId = $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
            ])
            ->assertCreated()
            ->json('child.id');
        $catalogActivityId = $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->json('categories.0.activities.0.id');
        $activityId = $this->withToken($token)
            ->postJson("/api/children/{$childId}/activities/{$catalogActivityId}", ['tp_override' => 3])
            ->assertCreated()
            ->json('activity_id');
        DB::table('ledgers')->insert([
            'child_id' => $childId,
            'commitment_id' => $activityId,
            'recorded_by' => $parent->id,
            'type' => 'bonus',
            'tp_amount' => 5,
            'conversion_rate_snapshot' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)
            ->deleteJson('/api/user', ['password' => 'incorrect'])
            ->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $parent->id]);

        $this->withToken($token)
            ->deleteJson('/api/user', ['password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('family_deleted', false);

        $this->assertDatabaseMissing('users', ['id' => $parent->id]);
        $this->assertDatabaseHas('users', ['id' => $otherParent->id, 'family_id' => $parent->family_id]);
        $this->assertDatabaseHas('children', ['id' => $childId]);
        $this->assertDatabaseHas('commitments', ['id' => $activityId]);
        $this->assertDatabaseHas('ledgers', ['child_id' => $childId, 'recorded_by' => null]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $otherParent->id,
            'type' => FamilyEventNotification::class,
        ]);
    }

    public function test_deleting_the_last_parent_removes_the_entire_family_and_point_history(): void
    {
        $token = $this->registerParent();
        $parent = User::query()->where('email', 'selam@example.com')->firstOrFail();
        $familyId = $parent->family_id;
        $childId = $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
            ])
            ->assertCreated()
            ->json('child.id');
        $catalogActivityId = $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->json('categories.0.activities.0.id');
        $activityId = $this->withToken($token)
            ->postJson("/api/children/{$childId}/activities/{$catalogActivityId}", ['tp_override' => 3])
            ->assertCreated()
            ->json('activity_id');
        DB::table('ledgers')->insert([
            'child_id' => $childId,
            'commitment_id' => $activityId,
            'recorded_by' => $parent->id,
            'type' => 'bonus',
            'tp_amount' => 5,
            'conversion_rate_snapshot' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)
            ->deleteJson('/api/user', ['password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('family_deleted', true);

        $this->assertDatabaseMissing('users', ['id' => $parent->id]);
        $this->assertDatabaseMissing('families', ['id' => $familyId]);
        $this->assertDatabaseMissing('children', ['id' => $childId]);
        $this->assertDatabaseMissing('commitments', ['id' => $activityId]);
        $this->assertDatabaseMissing('ledgers', ['child_id' => $childId]);
    }

    public function test_parent_can_save_notification_preferences_and_receive_family_events(): void
    {
        $token = $this->registerParent();
        $parent = User::query()->where('email', 'selam@example.com')->firstOrFail();
        $otherParent = User::create([
            'name' => 'Mimi Tesfaye',
            'email' => 'mimi@example.com',
            'phone' => '+251912345680',
            'password' => 'password123',
            'family_id' => $parent->family_id,
        ]);
        $childId = $this->withToken($token)
            ->postJson('/api/children', [
                'name' => 'Hana Abebe',
                'date_of_birth' => '2014-05-21',
            ])
            ->assertCreated()
            ->json('child.id');
        $activityId = $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->json('categories.0.activities.0.id');

        $activityId = $this->withToken($token)
            ->postJson("/api/children/{$childId}/activities/{$activityId}", ['tp_override' => 3])
            ->assertCreated()
            ->json('activity_id');
        $this->withToken($token)
            ->postJson("/api/children/{$childId}/ledger", [
                'type' => 'earning',
                'commitment_id' => $activityId,
            ])
            ->assertCreated();

        $this->assertDatabaseCount('notifications', 2);

        $this->actingAs($otherParent, 'sanctum')
            ->patchJson('/api/user/notification-preferences', ['points_recorded' => false])
            ->assertOk()
            ->assertJsonPath('preferences.points_recorded', false);
        $this->actingAs($otherParent, 'sanctum')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('name', 'Mimi Tesfaye');
        $this->assertFalse($otherParent->fresh()->notification_preferences['points_recorded']);

        $this->actingAs($parent, 'sanctum')
            ->postJson("/api/children/{$childId}/ledger", [
                'type' => 'earning',
                'commitment_id' => $activityId,
            ])
            ->assertCreated();

        $this->assertDatabaseCount('notifications', 2);
        $notifications = $this->actingAs($otherParent, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonCount(2, 'notifications')
            ->json('notifications');

        $this->actingAs($otherParent, 'sanctum')
            ->patchJson("/api/notifications/{$notifications[0]['id']}/read")
            ->assertOk();
        $this->actingAs($otherParent, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
        $this->actingAs($otherParent, 'sanctum')
            ->patchJson('/api/notifications/read-all')
            ->assertOk();
        $this->actingAs($otherParent, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
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
