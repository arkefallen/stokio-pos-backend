<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected string $loginEndpoint = '/api/auth/login';

    /**
     * ===================================
     * SUCCESSFUL LOGIN SCENARIOS
     * ===================================
     */

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                    'token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'message' => 'Login successful.',
                'data' => [
                    'token_type' => 'Bearer',
                    'user' => [
                        'email' => 'test@example.com',
                    ],
                ],
            ]);
    }

    public function test_login_updates_last_login_timestamp(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
            'last_login_at' => null,
        ]);

        $this->assertNull($user->last_login_at);

        $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_login_returns_valid_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Verify the token works
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'email' => 'test@example.com',
                ],
            ]);
    }

    public function test_login_with_custom_device_name(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
            'device_name' => 'iPhone 15 Pro',
        ]);

        $response->assertStatus(200);

        // Check that the token was created with the device name
        $user->refresh();
        $this->assertEquals('iPhone 15 Pro', $user->tokens->first()->name);
    }

    public function test_admin_can_login(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'AdminPass123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'admin@example.com',
            'password' => 'AdminPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'role' => 'admin',
                    ],
                ],
            ]);
    }

    public function test_manager_can_login(): void
    {
        $manager = User::factory()->manager()->create([
            'email' => 'manager@example.com',
            'password' => 'ManagerPass123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'manager@example.com',
            'password' => 'ManagerPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'role' => 'manager',
                    ],
                ],
            ]);
    }

    public function test_cashier_can_login(): void
    {
        $cashier = User::factory()->cashier()->create([
            'email' => 'cashier@example.com',
            'password' => 'CashierPass123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'cashier@example.com',
            'password' => 'CashierPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'role' => 'cashier',
                    ],
                ],
            ]);
    }

    /**
     * ===================================
     * FAILED LOGIN SCENARIOS
     * ===================================
     */

    public function test_login_fails_with_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'CorrectPassword123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'nonexistent@example.com',
            'password' => 'SomePassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['The provided credentials are incorrect.'],
            ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'inactive@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['Your account has been deactivated. Please contact an administrator.'],
            ]);
    }

    /**
     * ===================================
     * VALIDATION SCENARIOS
     * ===================================
     */

    public function test_login_requires_email(): void
    {
        $response = $this->postJson($this->loginEndpoint, [
            'password' => 'SomePassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'invalid-email',
            'password' => 'SomePassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_empty_request(): void
    {
        $response = $this->postJson($this->loginEndpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_device_name_max_length_validation(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
            'device_name' => str_repeat('a', 256), // 256 chars, max is 255
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_name']);
    }

    /**
     * ===================================
     * EDGE CASES
     * ===================================
     */

    public function test_login_email_case_behavior(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        // Try with uppercase email
        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'ValidPassword123!',
        ]);

        // Database collation determines case sensitivity
        // MySQL is typically case-insensitive, PostgreSQL is case-sensitive
        // Both behaviors are acceptable
        $this->assertTrue(
            in_array($response->status(), [200, 422]),
            'Expected status 200 (case-insensitive) or 422 (case-sensitive)'
        );
    }

    public function test_multiple_logins_create_multiple_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        // First login
        $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
            'device_name' => 'Device 1',
        ]);

        // Second login
        $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
            'device_name' => 'Device 2',
        ]);

        $user->refresh();
        $this->assertEquals(2, $user->tokens()->count());
    }

    public function test_login_with_whitespace_in_email(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => '  test@example.com  ',
            'password' => 'ValidPassword123!',
        ]);

        // Whitespace handling depends on whether middleware/validation trims input
        // Both behaviors are acceptable for this edge case
        $this->assertTrue(
            in_array($response->status(), [200, 422]),
            'Expected status 200 (trimmed) or 422 (exact match)'
        );
    }

    public function test_login_response_does_not_contain_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
    }

    public function test_login_with_sql_injection_attempt(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'ValidPassword123!',
        ]);

        $response = $this->postJson($this->loginEndpoint, [
            'email' => "test@example.com' OR '1'='1",
            'password' => 'ValidPassword123!',
        ]);

        $response->assertStatus(422);
    }
}
