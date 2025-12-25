<?php

namespace Tests\Unit\Auth\Actions;

use App\Modules\Auth\Actions\DeleteUser;
use App\Modules\Auth\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    protected DeleteUser $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new DeleteUser();
    }

    /**
     * ===================================
     * SUCCESSFUL DELETION
     * ===================================
     */

    public function test_execute_deletes_user(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();

        $result = $this->action->execute($userToDelete, $admin);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    }

    public function test_execute_returns_true_on_success(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();

        $result = $this->action->execute($userToDelete, $admin);

        $this->assertTrue($result);
    }

    public function test_admin_can_delete_cashier(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();

        $result = $this->action->execute($cashier, $admin);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $cashier->id]);
    }

    public function test_admin_can_delete_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        $result = $this->action->execute($manager, $admin);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $manager->id]);
    }

    public function test_admin_can_delete_another_admin(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $result = $this->action->execute($admin2, $admin1);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);
    }

    /**
     * ===================================
     * TOKEN CLEANUP
     * ===================================
     */

    public function test_execute_revokes_all_user_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();

        // Create tokens for the user
        $userToDelete->createToken('Device 1');
        $userToDelete->createToken('Device 2');
        $userToDelete->createToken('Device 3');

        $this->assertEquals(3, $userToDelete->tokens()->count());

        $this->action->execute($userToDelete, $admin);

        // Tokens should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $userToDelete->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_execute_deletes_tokens_before_user(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();
        $userToDelete->createToken('Test Device');

        // Should not throw foreign key constraint error
        $result = $this->action->execute($userToDelete, $admin);

        $this->assertTrue($result);
    }

    /**
     * ===================================
     * AUTHORIZATION - NON-ADMIN
     * ===================================
     */

    public function test_execute_throws_exception_when_deleted_by_cashier(): void
    {
        $cashier = User::factory()->cashier()->create();
        $userToDelete = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Only administrators can delete users.');

        $this->action->execute($userToDelete, $cashier);
    }

    public function test_execute_throws_exception_when_deleted_by_manager(): void
    {
        $manager = User::factory()->manager()->create();
        $userToDelete = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Only administrators can delete users.');

        $this->action->execute($userToDelete, $manager);
    }

    public function test_non_admin_deletion_does_not_delete_user(): void
    {
        $cashier = User::factory()->cashier()->create();
        $userToDelete = User::factory()->create();

        try {
            $this->action->execute($userToDelete, $cashier);
        } catch (AuthorizationException $e) {
            // Expected
        }

        // User should still exist
        $this->assertDatabaseHas('users', [
            'id' => $userToDelete->id,
        ]);
    }

    /**
     * ===================================
     * SELF-DELETION PREVENTION
     * ===================================
     */

    public function test_execute_throws_exception_on_self_deletion(): void
    {
        $admin = User::factory()->admin()->create();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You cannot delete your own account.');

        $this->action->execute($admin, $admin);
    }

    public function test_self_deletion_attempt_does_not_delete_user(): void
    {
        $admin = User::factory()->admin()->create();

        try {
            $this->action->execute($admin, $admin);
        } catch (AuthorizationException $e) {
            // Expected
        }

        // Admin should still exist
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    /**
     * ===================================
     * TRANSACTION BEHAVIOR
     * ===================================
     */

    public function test_execute_is_transactional(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();
        $userToDelete->createToken('Device 1');

        // If transaction works correctly, both user and tokens are deleted together
        $this->action->execute($userToDelete, $admin);

        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $userToDelete->id,
        ]);
    }

    /**
     * ===================================
     * EDGE CASES
     * ===================================
     */

    public function test_delete_inactive_user(): void
    {
        $admin = User::factory()->admin()->create();
        $inactiveUser = User::factory()->inactive()->create();

        $result = $this->action->execute($inactiveUser, $admin);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $inactiveUser->id]);
    }

    public function test_delete_user_with_no_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();

        // User has no tokens
        $this->assertEquals(0, $userToDelete->tokens()->count());

        $result = $this->action->execute($userToDelete, $admin);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }

    public function test_only_target_user_is_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $this->action->execute($user2, $admin);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $user1->id]);
        $this->assertDatabaseMissing('users', ['id' => $user2->id]);
        $this->assertDatabaseHas('users', ['id' => $user3->id]);
    }

    public function test_only_target_user_tokens_are_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->createToken('Admin Device');

        $user1 = User::factory()->create();
        $user1->createToken('User1 Device');

        $userToDelete = User::factory()->create();
        $userToDelete->createToken('Delete Me Device');

        $this->action->execute($userToDelete, $admin);

        // Other users' tokens should still exist
        $this->assertEquals(1, $admin->fresh()->tokens()->count());
        $this->assertEquals(1, $user1->fresh()->tokens()->count());
    }
}
