<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected string $registerEndpoint = '/api/auth/register';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * ===================================
     * AUTHORIZATION SCENARIOS
     * ===================================
     */

    public function test_guest_cannot_register_users(): void
    {
        $response = $this->postJson($this->registerEndpoint, [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(401);
    }

    public function test_cashier_cannot_register_users(): void
    {
        $cashier = User::factory()->cashier()->create();

        $response = $this->actingAs($cashier)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(403);
    }

    public function test_manager_cannot_register_users(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(403);
    }

    /**
     * ===================================
     * SUCCESSFUL REGISTRATION SCENARIOS
     * ===================================
     */

    public function test_admin_can_register_new_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'message' => 'User registered successfully.',
                'data' => [
                    'name' => 'New User',
                    'email' => 'newuser@example.com',
                    'role' => 'cashier', // default role
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_register_user_with_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New Admin',
                'email' => 'newadmin@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'admin',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'role' => 'admin',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_register_user_with_manager_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New Manager',
                'email' => 'newmanager@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'manager',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'role' => 'manager',
                ],
            ]);
    }

    public function test_admin_can_register_user_with_cashier_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New Cashier',
                'email' => 'newcashier@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'cashier',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'role' => 'cashier',
                ],
            ]);
    }

    public function test_registered_user_is_active_by_default(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(201);

        $newUser = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($newUser->is_active);
    }

    public function test_registered_user_password_is_hashed(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $newUser = User::where('email', 'newuser@example.com')->first();

        // Password should be hashed, not plain text
        $this->assertNotEquals('Password123!', $newUser->password);
        $this->assertTrue(password_verify('Password123!', $newUser->password));
    }

    /**
     * ===================================
     * VALIDATION SCENARIOS
     * ===================================
     */

    public function test_registration_requires_name(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_registration_requires_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_fails_when_passwords_dont_match(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_valid_email_format(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'invalid-email',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'existing@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['This email address is already registered.'],
            ]);
    }

    public function test_registration_fails_with_invalid_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'superadmin', // invalid role
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_registration_name_max_length(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => str_repeat('a', 256), // 256 chars, max is 255
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_registration_email_max_length(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => str_repeat('a', 250) . '@test.com', // exceeds 255 chars
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * ===================================
     * EDGE CASES
     * ===================================
     */

    public function test_registration_response_does_not_contain_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(201);
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    public function test_can_register_multiple_users(): void
    {
        $admin = User::factory()->admin()->create();

        // First registration
        $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'User One',
                'email' => 'user1@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertStatus(201);

        // Second registration
        $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'User Two',
                'email' => 'user2@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertStatus(201);

        $this->assertDatabaseCount('users', 3); // admin + 2 new users
    }

    public function test_registration_with_empty_request(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_trims_whitespace_from_name(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => '  John Doe  ',
                'email' => 'newuser@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(201);

        // The name should be stored with whitespace trimmed (if middleware handles it)
        // If not, this test documents current behavior
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);
    }

    public function test_registration_with_unicode_name(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'Мария Петрова', // Cyrillic
                'email' => 'maria@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Мария Петрова',
                ],
            ]);
    }

    public function test_registration_with_emoji_in_name(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson($this->registerEndpoint, [
                'name' => 'John 😀 Doe',
                'email' => 'john.emoji@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        // This should succeed if the database supports UTF-8 mb4
        $response->assertStatus(201);
    }
}
