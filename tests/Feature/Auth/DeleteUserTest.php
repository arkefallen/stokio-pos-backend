<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ===================================
     * AUTHORIZATION SCENARIOS
     * ===================================
     */

    public function test_guest_cannot_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/auth/users/{$user->id}");

        $response->assertStatus(401);
    }

    public function test_cashier_cannot_delete_user(): void
    {
        $cashier = User::factory()->cashier()->create();
        $userToDelete = User::factory()->create();

        Sanctum::actingAs($cashier);

        $response = $this->deleteJson("/api/auth/users/{$userToDelete->id}");

        $response->assertStatus(403);
    }

    public function test_manager_cannot_delete_user(): void
    {
        $manager = User::factory()->manager()->create();
        $userToDelete = User::factory()->create();

        Sanctum::actingAs($manager);

        $response = $this->deleteJson("/api/auth/users/{$userToDelete->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/auth/users/{$userToDelete->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User deleted successfully.',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    }

    /**
     * ===================================
     * SUCCESSFUL DELETION SCENARIOS
     * ===================================
     */

    public function test_admin_can_delete_cashier(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/auth/users/{$cashier->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $cashier->id]);
    }

    public function test_admin_can_delete_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/auth/users/{$manager->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $manager->id]);
    }

    public function test_admin_can_delete_another_admin(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        Sanctum::actingAs($admin1);

        $response = $this->deleteJson("/api/auth/users/{$admin2->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);
    }

    public function test_admin_can_delete_inactive_user(): void
    {
        $admin = User::factory()->admin()->create();
        $inactiveUser = User::factory()->inactive()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/auth/users/{$inactiveUser->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $inactiveUser->id]);
    }

    public function test_deleting_user_revokes_all_their_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();

        // Create multiple tokens for the user
        $userToDelete->createToken('Device 1');
        $userToDelete->createToken('Device 2');
        $userToDelete->createToken('Device 3');

        $this->assertEquals(3, $userToDelete->tokens()->count());

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/auth/users/{$userToDelete->id}");

        $response->assertStatus(200);

        // Verify tokens are also deleted (cascaded or explicitly)
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $userToDelete->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * ===================================
     * SELF-DELETION PREVENTION
     * ===================================
     */

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/auth/users/{$admin->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    /**
     * ===================================
     * ERROR SCENARIOS
     * ===================================
     */

    public function test_delete_nonexistent_user_returns_404(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/auth/users/999999');

        $response->assertStatus(404);
    }

    public function test_delete_with_invalid_id_format(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/auth/users/invalid-id');

        $response->assertStatus(404);
    }

    public function test_delete_with_negative_id(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/auth/users/-1');

        $response->assertStatus(404);
    }

    /**
     * ===================================
     * EDGE CASES
     * ===================================
     */

    public function test_only_the_specified_user_is_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $user3 = User::factory()->create(['email' => 'user3@example.com']);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/auth/users/{$user2->id}");

        $response->assertStatus(200);

        // Only user2 should be deleted
        $this->assertDatabaseMissing('users', ['id' => $user2->id]);
        $this->assertDatabaseHas('users', ['id' => $user1->id]);
        $this->assertDatabaseHas('users', ['id' => $user3->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_delete_user_is_transactional(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();
        $userToDelete->createToken('Test Device');

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/auth/users/{$userToDelete->id}");

        $response->assertStatus(200);

        // Both user and tokens should be deleted together
        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $userToDelete->id,
        ]);
    }

    public function test_deleting_user_with_currently_logged_in_session(): void
    {
        $admin = User::factory()->admin()->create();
        $userToDelete = User::factory()->create();

        // User logs in and gets a token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $userToDelete->email,
            'password' => 'password',
        ]);
        $userToken = $loginResponse->json('data.token');

        // Admin deletes the user
        Sanctum::actingAs($admin);
        $this->deleteJson("/api/auth/users/{$userToDelete->id}")
            ->assertStatus(200);

        // Refresh application to clear any cached state
        $this->refreshApplication();

        // The user's token should no longer work (user is deleted, so token lookup fails)
        $response = $this->withHeader('Authorization', "Bearer {$userToken}")
            ->getJson('/api/auth/me');

        // Should be 401 (unauthenticated) since user and token are deleted
        $response->assertStatus(401);
    }

    public function test_multiple_admins_can_exist_after_deleting_one(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        $admin3 = User::factory()->admin()->create();

        Sanctum::actingAs($admin1);

        $this->deleteJson("/api/auth/users/{$admin2->id}")
            ->assertStatus(200);

        // admin1 and admin3 should still exist
        $this->assertDatabaseHas('users', [
            'id' => $admin1->id,
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $admin3->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_count_decreases_after_deletion(): void
    {
        User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        $deletingAdmin = User::factory()->admin()->create();

        $initialAdminCount = User::where('role', 'admin')->count();
        $this->assertEquals(3, $initialAdminCount);

        Sanctum::actingAs($deletingAdmin);

        $this->deleteJson("/api/auth/users/{$admin2->id}")
            ->assertStatus(200);

        $finalAdminCount = User::where('role', 'admin')->count();
        $this->assertEquals(2, $finalAdminCount);
    }
}
