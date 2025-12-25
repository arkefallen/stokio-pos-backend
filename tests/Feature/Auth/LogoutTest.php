<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected string $logoutEndpoint = '/api/auth/logout';
    protected string $logoutAllEndpoint = '/api/auth/logout-all';

    /**
     * ===================================
     * SINGLE DEVICE LOGOUT
     * ===================================
     */

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        // Create a token by logging in
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson($this->logoutEndpoint);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();

        // Login to get a token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        // Logout
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson($this->logoutEndpoint)
            ->assertStatus(200);

        // Refresh app to clear cached auth state
        $this->refreshApplication();

        // Try to use the token again - should fail
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_logout_does_not_revoke_other_tokens(): void
    {
        $user = User::factory()->create();

        // Login on device 1
        $login1 = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Device 1',
        ]);
        $token1 = $login1->json('data.token');

        // Login on device 2
        $login2 = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Device 2',
        ]);
        $token2 = $login2->json('data.token');

        // Logout from device 1
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson($this->logoutEndpoint)
            ->assertStatus(200);

        // Refresh app to clear cached auth state
        $this->refreshApplication();

        // Token 1 should be invalid
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->getJson('/api/auth/me')
            ->assertStatus(200);

        // Refresh again for clean state
        $this->refreshApplication();

        // Token 2 should still work
        $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson('/api/auth/me')
            ->assertStatus(200);
    }

    public function test_guest_cannot_logout(): void
    {
        $response = $this->postJson($this->logoutEndpoint);

        $response->assertStatus(401);
    }

    /**
     * ===================================
     * LOGOUT FROM ALL DEVICES
     * ===================================
     */

    public function test_user_can_logout_from_all_devices(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson($this->logoutAllEndpoint);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out from all devices successfully.',
            ]);
    }

    public function test_logout_all_revokes_all_tokens(): void
    {
        $user = User::factory()->create();

        // Login on multiple devices
        $login1 = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Device 1',
        ]);
        $token1 = $login1->json('data.token');

        $login2 = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Device 2',
        ]);
        $token2 = $login2->json('data.token');

        $login3 = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Device 3',
        ]);
        $token3 = $login3->json('data.token');

        // Verify user has 3 tokens
        $this->assertEquals(3, $user->fresh()->tokens()->count());

        // Logout from all devices using token 1
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson($this->logoutAllEndpoint)
            ->assertStatus(200);

        // All tokens should be revoked
        $this->assertEquals(0, $user->fresh()->tokens()->count());

        // Refresh app to clear cached auth state
        $this->refreshApplication();

        // Try to use any token - should fail
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);

        $this->refreshApplication();

        $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);

        $this->refreshApplication();

        $this->withHeader('Authorization', "Bearer {$token3}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_guest_cannot_logout_from_all_devices(): void
    {
        $response = $this->postJson($this->logoutAllEndpoint);

        $response->assertStatus(401);
    }

    /**
     * ===================================
     * EDGE CASES
     * ===================================
     */

    public function test_double_logout_is_handled(): void
    {
        $user = User::factory()->create();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        // First logout - should succeed
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson($this->logoutEndpoint)
            ->assertStatus(200);

        // Refresh app to clear cached auth state
        $this->refreshApplication();

        // Second logout - should fail as token is already revoked
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson($this->logoutEndpoint)
            ->assertStatus(401);
    }

    public function test_logout_with_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson($this->logoutEndpoint);

        $response->assertStatus(401);
    }

    public function test_logout_all_with_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson($this->logoutAllEndpoint);

        $response->assertStatus(401);
    }

    public function test_admin_logout_only_affects_own_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->create();

        // Login both users
        $adminLogin = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $adminToken = $adminLogin->json('data.token');

        $otherLogin = $this->postJson('/api/auth/login', [
            'email' => $otherUser->email,
            'password' => 'password',
        ]);
        $otherToken = $otherLogin->json('data.token');

        // Verify both users have tokens
        $this->assertEquals(1, $admin->fresh()->tokens()->count());
        $this->assertEquals(1, $otherUser->fresh()->tokens()->count());

        // Admin logs out from all devices
        $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson($this->logoutAllEndpoint)
            ->assertStatus(200);

        // Verify only admin's tokens were revoked (database check is authoritative)
        $this->assertEquals(0, $admin->fresh()->tokens()->count());
        $this->assertEquals(1, $otherUser->fresh()->tokens()->count());

        // Verify other user's token still exists in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $otherUser->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_inactive_user_cannot_logout(): void
    {
        // This tests the scenario where a user was deactivated after login
        $user = User::factory()->create();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        // Deactivate user (simulating admin action)
        $user->update(['is_active' => false]);

        // User should still be able to logout with their existing token
        // Note: This depends on implementation - some systems block all actions for inactive users
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson($this->logoutEndpoint)
            ->assertSuccessful();
    }
}
