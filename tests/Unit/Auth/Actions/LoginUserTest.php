<?php

namespace Tests\Unit\Auth\Actions;

use App\Modules\Auth\Actions\LoginUser;
use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    use RefreshDatabase;

    protected LoginUser $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new LoginUser();
    }

    /**
     * ===================================
     * SUCCESSFUL LOGIN
     * ===================================
     */

    public function test_execute_returns_user_and_token_on_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->action->execute('test@example.com', 'password123');

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertIsString($result['token']);
        $this->assertNotEmpty($result['token']);
    }

    public function test_execute_creates_token_with_device_name(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->action->execute('test@example.com', 'password123', 'iPhone 15');

        $user->refresh();
        $token = $user->tokens()->first();

        $this->assertEquals('iPhone 15', $token->name);
    }

    public function test_execute_creates_token_with_default_device_name(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->action->execute('test@example.com', 'password123');

        $user->refresh();
        $token = $user->tokens()->first();

        $this->assertEquals('default', $token->name);
    }

    public function test_execute_updates_last_login_at(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'last_login_at' => null,
        ]);

        $this->assertNull($user->last_login_at);

        $beforeLogin = now();
        $this->action->execute('test@example.com', 'password123');
        $afterLogin = now();

        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertTrue($user->last_login_at->between($beforeLogin->subSecond(), $afterLogin->addSecond()));
    }

    /**
     * ===================================
     * FAILED LOGIN - INVALID CREDENTIALS
     * ===================================
     */

    public function test_execute_throws_exception_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->expectException(ValidationException::class);

        $this->action->execute('test@example.com', 'wrong-password');
    }

    public function test_execute_throws_exception_with_nonexistent_email(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->execute('nonexistent@example.com', 'any-password');
    }

    public function test_wrong_password_error_message(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        try {
            $this->action->execute('test@example.com', 'wrong-password');
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertContains('The provided credentials are incorrect.', $errors['email']);
        }
    }

    /**
     * ===================================
     * FAILED LOGIN - INACTIVE USER
     * ===================================
     */

    public function test_execute_throws_exception_for_inactive_user(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->expectException(ValidationException::class);

        $this->action->execute('inactive@example.com', 'password123');
    }

    public function test_inactive_user_error_message(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
        ]);

        try {
            $this->action->execute('inactive@example.com', 'password123');
            $this->fail('ValidationException should have been thrown');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertContains(
                'Your account has been deactivated. Please contact an administrator.',
                $errors['email']
            );
        }
    }

    /**
     * ===================================
     * TOKEN MANAGEMENT
     * ===================================
     */

    public function test_multiple_logins_create_multiple_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->action->execute('test@example.com', 'password123', 'Device 1');
        $this->action->execute('test@example.com', 'password123', 'Device 2');
        $this->action->execute('test@example.com', 'password123', 'Device 3');

        $user->refresh();
        $this->assertEquals(3, $user->tokens()->count());
    }

    public function test_returned_token_is_valid_plain_text_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->action->execute('test@example.com', 'password123');

        // Token should contain a pipe character (format: id|hash)
        $this->assertStringContainsString('|', $result['token']);
    }

    /**
     * ===================================
     * EDGE CASES
     * ===================================
     */

    public function test_login_does_not_create_token_on_failure(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        try {
            $this->action->execute('test@example.com', 'wrong-password');
        } catch (ValidationException $e) {
            // Expected
        }

        $user->refresh();
        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_login_does_not_update_last_login_on_failure(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
            'last_login_at' => null,
        ]);

        try {
            $this->action->execute('test@example.com', 'wrong-password');
        } catch (ValidationException $e) {
            // Expected
        }

        $user->refresh();
        $this->assertNull($user->last_login_at);
    }

    public function test_login_with_empty_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->expectException(ValidationException::class);

        $this->action->execute('test@example.com', '');
    }
}
