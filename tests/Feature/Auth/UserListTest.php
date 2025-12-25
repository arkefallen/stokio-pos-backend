<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    protected string $usersEndpoint = '/api/auth/users';

    /**
     * ===================================
     * AUTHORIZATION SCENARIOS
     * ===================================
     */

    public function test_guest_cannot_view_users_list(): void
    {
        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(401);
    }

    public function test_cashier_cannot_view_users_list(): void
    {
        $cashier = User::factory()->cashier()->create();

        Sanctum::actingAs($cashier);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(403);
    }

    public function test_manager_cannot_view_users_list(): void
    {
        $manager = User::factory()->manager()->create();

        Sanctum::actingAs($manager);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_users_list(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'total',
            ]);
    }

    /**
     * ===================================
     * SUCCESSFUL RETRIEVAL SCENARIOS
     * ===================================
     */

    public function test_users_list_includes_all_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(5)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200)
            ->assertJson([
                'total' => 6, // admin + 5 other users
            ]);

        $this->assertCount(6, $response->json('data'));
    }

    public function test_users_list_returns_correct_user_data(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $cashier = User::factory()->cashier()->create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200);

        $users = $response->json('data');
        $emails = collect($users)->pluck('email')->toArray();

        $this->assertContains('admin@example.com', $emails);
        $this->assertContains('cashier@example.com', $emails);
    }

    public function test_users_list_includes_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200);

        $firstUser = $response->json('data.0');

        $this->assertArrayHasKey('id', $firstUser);
        $this->assertArrayHasKey('name', $firstUser);
        $this->assertArrayHasKey('email', $firstUser);
        $this->assertArrayHasKey('role', $firstUser);
        $this->assertArrayHasKey('is_active', $firstUser);
        $this->assertArrayHasKey('is_online', $firstUser);
        $this->assertArrayHasKey('last_login_at', $firstUser);
        $this->assertArrayHasKey('created_at', $firstUser);
    }

    public function test_users_list_does_not_include_password(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200);

        foreach ($response->json('data') as $user) {
            $this->assertArrayNotHasKey('password', $user);
            $this->assertArrayNotHasKey('remember_token', $user);
        }
    }

    public function test_users_list_sorted_by_creation_date_descending(): void
    {
        $admin = User::factory()->admin()->create();

        // Create users at different times
        $user1 = User::factory()->create(['created_at' => now()->subDays(3)]);
        $user2 = User::factory()->create(['created_at' => now()->subDays(1)]);
        $user3 = User::factory()->create(['created_at' => now()->subDays(2)]);

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200);

        $users = $response->json('data');

        // Admin was created most recently, so it should be first
        $this->assertEquals($admin->id, $users[0]['id']);
        // Then user2 (1 day ago)
        $this->assertEquals($user2->id, $users[1]['id']);
        // Then user3 (2 days ago)
        $this->assertEquals($user3->id, $users[2]['id']);
        // Finally user1 (3 days ago)
        $this->assertEquals($user1->id, $users[3]['id']);
    }

    /**
     * ===================================
     * ONLINE STATUS TESTS
     * ===================================
     */

    public function test_user_is_online_if_logged_in_within_30_minutes(): void
    {
        $admin = User::factory()->admin()->create();
        $recentUser = User::factory()->create([
            'last_login_at' => now()->subMinutes(15), // 15 minutes ago
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200);

        $users = collect($response->json('data'));
        $recentUserData = $users->firstWhere('id', $recentUser->id);

        $this->assertTrue($recentUserData['is_online']);
    }

    public function test_user_is_offline_if_logged_in_more_than_30_minutes_ago(): void
    {
        $admin = User::factory()->admin()->create();
        $oldUser = User::factory()->create([
            'last_login_at' => now()->subMinutes(45), // 45 minutes ago
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200);

        $users = collect($response->json('data'));
        $oldUserData = $users->firstWhere('id', $oldUser->id);

        $this->assertFalse($oldUserData['is_online']);
    }

    public function test_user_is_offline_if_never_logged_in(): void
    {
        $admin = User::factory()->admin()->create();
        $newUser = User::factory()->create([
            'last_login_at' => null,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200);

        $users = collect($response->json('data'));
        $newUserData = $users->firstWhere('id', $newUser->id);

        $this->assertFalse($newUserData['is_online']);
    }

    /**
     * ===================================
     * EDGE CASES
     * ===================================
     */

    public function test_empty_users_list_when_only_admin_exists(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200)
            ->assertJson([
                'total' => 1, // Just the admin
            ]);
    }

    public function test_users_list_includes_inactive_users(): void
    {
        $admin = User::factory()->admin()->create();
        $inactiveUser = User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200);

        $users = collect($response->json('data'));
        $inactiveUserData = $users->firstWhere('id', $inactiveUser->id);

        $this->assertNotNull($inactiveUserData);
        $this->assertFalse($inactiveUserData['is_active']);
    }

    public function test_users_list_includes_all_roles(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->manager()->create();
        User::factory()->cashier()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200);

        $users = collect($response->json('data'));
        $roles = $users->pluck('role')->unique()->toArray();

        $this->assertContains('admin', $roles);
        $this->assertContains('manager', $roles);
        $this->assertContains('cashier', $roles);
    }

    public function test_large_number_of_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(100)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson($this->usersEndpoint);

        $response->assertStatus(200)
            ->assertJson([
                'total' => 101, // admin + 100 users
            ]);
    }
}
