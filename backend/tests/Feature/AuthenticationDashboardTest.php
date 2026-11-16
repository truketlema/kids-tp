<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
