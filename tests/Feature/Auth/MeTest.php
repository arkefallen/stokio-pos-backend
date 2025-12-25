<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    protected string $meEndpoint = '/api/auth/me';

    /**
     * ===================================
     * SUCCESSFUL SCENARIOS
     * ===================================
     */

    public function test_authenticated_user_can_get_own_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'cashier',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'is_active',
                    'email_verified_at',
                    'last_login_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'role' => 'cashier',
                    'is_active' => true,
                ],
            ]);
    }

    public function test_admin_can_get_own_profile(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                ],
            ]);
    }

    public function test_manager_can_get_own_profile(): void
    {
        $manager = User::factory()->manager()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
        ]);

        Sanctum::actingAs($manager);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Manager User',
                    'email' => 'manager@example.com',
                    'role' => 'manager',
                ],
            ]);
    }

    public function test_response_includes_last_login_timestamp(): void
    {
        $loginTime = now()->subHour();

        $user = User::factory()->create([
            'last_login_at' => $loginTime,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.last_login_at'));
    }

    public function test_response_includes_null_last_login_for_new_user(): void
    {
        $user = User::factory()->create([
            'last_login_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200);
        $this->assertNull($response->json('data.last_login_at'));
    }

    /**
     * ===================================
     * AUTHENTICATION SCENARIOS
     * ===================================
     */

    public function test_guest_cannot_access_profile(): void
    {
        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(401);
    }

    public function test_invalid_token_cannot_access_profile(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson($this->meEndpoint);

        $response->assertStatus(401);
    }

    public function test_expired_token_cannot_access_profile(): void
    {
        $user = User::factory()->create();

        // Create a token and then delete it to simulate expiration
        $token = $user->createToken('test')->plainTextToken;
        $user->tokens()->delete();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson($this->meEndpoint);

        $response->assertStatus(401);
    }

    /**
     * ===================================
     * DATA INTEGRITY
     * ===================================
     */

    public function test_password_is_not_included_in_profile(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    public function test_remember_token_is_not_included_in_profile(): void
    {
        $user = User::factory()->create([
            'remember_token' => 'some-token',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('remember_token', $response->json('data'));
    }

    public function test_profile_returns_iso8601_datetime_format(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200);

        $createdAt = $response->json('data.created_at');
        $updatedAt = $response->json('data.updated_at');

        // Verify ISO8601 format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $createdAt
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $updatedAt
        );
    }

    /**
     * ===================================
     * EDGE CASES
     * ===================================
     */

    public function test_profile_reflects_latest_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
        ]);

        Sanctum::actingAs($user);

        // Update user directly in database
        $user->update(['name' => 'Updated Name']);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);
    }

    public function test_profile_shows_inactive_status(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson($this->meEndpoint);

        // Even inactive users can view their profile if they have a valid token
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_active' => false,
                ],
            ]);
    }

    public function test_concurrent_profile_requests(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        // Multiple requests should return consistent data
        $response1 = $this->getJson($this->meEndpoint);
        $response2 = $this->getJson($this->meEndpoint);
        $response3 = $this->getJson($this->meEndpoint);

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);

        $this->assertEquals(
            $response1->json('data.id'),
            $response2->json('data.id')
        );
        $this->assertEquals(
            $response2->json('data.id'),
            $response3->json('data.id')
        );
    }

    public function test_profile_with_special_characters_in_name(): void
    {
        $user = User::factory()->create([
            'name' => "O'Brien-Smith, Jr.",
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson($this->meEndpoint);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => "O'Brien-Smith, Jr.",
                ],
            ]);
    }
}
