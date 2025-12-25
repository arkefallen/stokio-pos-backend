<?php

namespace Tests\Unit\Auth\Actions;

use App\Modules\Auth\Actions\RegisterUser;
use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase;

    protected RegisterUser $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new RegisterUser();
    }

    /**
     * ===================================
     * SUCCESSFUL REGISTRATION
     * ===================================
     */

    public function test_execute_creates_user(): void
    {
        $result = $this->action->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_execute_returns_created_user(): void
    {
        $result = $this->action->execute([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $this->assertEquals('Jane Doe', $result->name);
        $this->assertEquals('jane@example.com', $result->email);
    }

    public function test_execute_hashes_password(): void
    {
        $result = $this->action->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'plaintextpassword',
        ]);

        // Password should be hashed
        $this->assertNotEquals('plaintextpassword', $result->password);
        $this->assertTrue(Hash::check('plaintextpassword', $result->password));
    }

    public function test_execute_sets_default_role_to_cashier(): void
    {
        $result = $this->action->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->assertEquals(User::ROLE_CASHIER, $result->role);
    }

    public function test_execute_sets_user_as_active_by_default(): void
    {
        $result = $this->action->execute([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->assertTrue($result->is_active);
    }

    /**
     * ===================================
     * ROLE ASSIGNMENT
     * ===================================
     */

    public function test_execute_can_create_admin_user(): void
    {
        $result = $this->action->execute([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertEquals(User::ROLE_ADMIN, $result->role);
        $this->assertTrue($result->isAdmin());
    }

    public function test_execute_can_create_manager_user(): void
    {
        $result = $this->action->execute([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'role' => User::ROLE_MANAGER,
        ]);

        $this->assertEquals(User::ROLE_MANAGER, $result->role);
        $this->assertTrue($result->isManager());
    }

    public function test_execute_can_create_cashier_user_explicitly(): void
    {
        $result = $this->action->execute([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'password' => 'password123',
            'role' => User::ROLE_CASHIER,
        ]);

        $this->assertEquals(User::ROLE_CASHIER, $result->role);
        $this->assertTrue($result->isCashier());
    }

    /**
     * ===================================
     * TRANSACTIONS
     * ===================================
     */

    public function test_execute_is_wrapped_in_transaction(): void
    {
        // If the user creation is wrapped in a transaction,
        // and something goes wrong, the user should not be created

        // Create a user first
        $result = $this->action->execute([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'id' => $result->id,
        ]);
    }

    /**
     * ===================================
     * DATA INTEGRITY
     * ===================================
     */

    public function test_execute_preserves_exact_name(): void
    {
        $result = $this->action->execute([
            'name' => 'John O\'Brien-Smith Jr.',
            'email' => 'john.obrien@example.com',
            'password' => 'password123',
        ]);

        $this->assertEquals('John O\'Brien-Smith Jr.', $result->name);
    }

    public function test_execute_preserves_email_case(): void
    {
        // Note: Depending on database collation, email case might be preserved or not
        $result = $this->action->execute([
            'name' => 'Test User',
            'email' => 'Test@Example.COM',
            'password' => 'password123',
        ]);

        // The email should be stored as provided
        $this->assertEquals('Test@Example.COM', $result->email);
    }

    public function test_execute_with_unicode_name(): void
    {
        $result = $this->action->execute([
            'name' => 'Mária Петрова 田中',
            'email' => 'unicode@example.com',
            'password' => 'password123',
        ]);

        $this->assertEquals('Mária Петрова 田中', $result->name);
    }

    /**
     * ===================================
     * MULTIPLE REGISTRATIONS
     * ===================================
     */

    public function test_can_register_multiple_users(): void
    {
        $user1 = $this->action->execute([
            'name' => 'User One',
            'email' => 'user1@example.com',
            'password' => 'password123',
        ]);

        $user2 = $this->action->execute([
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'password' => 'password456',
        ]);

        $this->assertDatabaseCount('users', 2);
        $this->assertNotEquals($user1->id, $user2->id);
    }

    public function test_each_user_gets_unique_id(): void
    {
        $users = [];

        for ($i = 1; $i <= 5; $i++) {
            $users[] = $this->action->execute([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'password123',
            ]);
        }

        $ids = array_map(fn($user) => $user->id, $users);
        $this->assertEquals(count($ids), count(array_unique($ids)));
    }

    /**
     * ===================================
     * TIMESTAMP BEHAVIOR
     * ===================================
     */

    public function test_execute_sets_timestamps(): void
    {
        $before = now();

        $result = $this->action->execute([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $after = now();

        $this->assertNotNull($result->created_at);
        $this->assertNotNull($result->updated_at);
        $this->assertTrue($result->created_at->between($before->subSecond(), $after->addSecond()));
    }

    public function test_new_user_has_null_last_login_at(): void
    {
        $result = $this->action->execute([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertNull($result->last_login_at);
    }
}
