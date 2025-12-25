<?php

namespace Tests\Unit\Auth\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ===================================
     * ROLE CONSTANTS
     * ===================================
     */

    public function test_role_constants_are_defined(): void
    {
        $this->assertEquals('admin', User::ROLE_ADMIN);
        $this->assertEquals('manager', User::ROLE_MANAGER);
        $this->assertEquals('cashier', User::ROLE_CASHIER);
    }

    public function test_roles_array_contains_all_roles(): void
    {
        $this->assertContains(User::ROLE_ADMIN, User::ROLES);
        $this->assertContains(User::ROLE_MANAGER, User::ROLES);
        $this->assertContains(User::ROLE_CASHIER, User::ROLES);
        $this->assertCount(3, User::ROLES);
    }

    /**
     * ===================================
     * ROLE CHECK METHODS
     * ===================================
     */

    public function test_isAdmin_returns_true_for_admin(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->isAdmin());
    }

    public function test_isAdmin_returns_false_for_non_admin(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create();

        $this->assertFalse($manager->isAdmin());
        $this->assertFalse($cashier->isAdmin());
    }

    public function test_isManager_returns_true_for_manager(): void
    {
        $user = User::factory()->manager()->create();

        $this->assertTrue($user->isManager());
    }

    public function test_isManager_returns_false_for_non_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();

        $this->assertFalse($admin->isManager());
        $this->assertFalse($cashier->isManager());
    }

    public function test_isCashier_returns_true_for_cashier(): void
    {
        $user = User::factory()->cashier()->create();

        $this->assertTrue($user->isCashier());
    }

    public function test_isCashier_returns_false_for_non_cashier(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();

        $this->assertFalse($admin->isCashier());
        $this->assertFalse($manager->isCashier());
    }

    public function test_hasRole_returns_true_for_matching_role(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($manager->hasRole('manager'));
        $this->assertTrue($cashier->hasRole('cashier'));
    }

    public function test_hasRole_returns_false_for_non_matching_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->hasRole('manager'));
        $this->assertFalse($admin->hasRole('cashier'));
        $this->assertFalse($admin->hasRole('superadmin'));
    }

    /**
     * ===================================
     * COMBINED ROLE METHODS
     * ===================================
     */

    public function test_isAdminOrManager_returns_true_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdminOrManager());
    }

    public function test_isAdminOrManager_returns_true_for_manager(): void
    {
        $manager = User::factory()->manager()->create();

        $this->assertTrue($manager->isAdminOrManager());
    }

    public function test_isAdminOrManager_returns_false_for_cashier(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->assertFalse($cashier->isAdminOrManager());
    }

    /**
     * ===================================
     * SCOPES
     * ===================================
     */

    public function test_scope_active_returns_only_active_users(): void
    {
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->count(2)->inactive()->create();

        $activeUsers = User::active()->get();

        $this->assertCount(3, $activeUsers);
        $activeUsers->each(function ($user) {
            $this->assertTrue($user->is_active);
        });
    }

    public function test_scope_active_excludes_inactive_users(): void
    {
        $activeUser = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->inactive()->create();

        $activeUsers = User::active()->get();

        $this->assertTrue($activeUsers->contains($activeUser));
        $this->assertFalse($activeUsers->contains($inactiveUser));
    }

    public function test_scope_byRole_filters_by_admin(): void
    {
        User::factory()->admin()->create();
        User::factory()->manager()->create();
        User::factory()->cashier()->create();

        $admins = User::byRole('admin')->get();

        $this->assertCount(1, $admins);
        $this->assertTrue($admins->first()->isAdmin());
    }

    public function test_scope_byRole_filters_by_manager(): void
    {
        User::factory()->admin()->create();
        User::factory()->manager()->count(2)->create();
        User::factory()->cashier()->create();

        $managers = User::byRole('manager')->get();

        $this->assertCount(2, $managers);
        $managers->each(function ($user) {
            $this->assertTrue($user->isManager());
        });
    }

    public function test_scope_byRole_filters_by_cashier(): void
    {
        User::factory()->admin()->create();
        User::factory()->manager()->create();
        User::factory()->cashier()->count(3)->create();

        $cashiers = User::byRole('cashier')->get();

        $this->assertCount(3, $cashiers);
        $cashiers->each(function ($user) {
            $this->assertTrue($user->isCashier());
        });
    }

    public function test_scopes_can_be_chained(): void
    {
        User::factory()->admin()->create(['is_active' => true]);
        User::factory()->admin()->inactive()->create();
        User::factory()->cashier()->create(['is_active' => true]);

        $activeAdmins = User::active()->byRole('admin')->get();

        $this->assertCount(1, $activeAdmins);
        $this->assertTrue($activeAdmins->first()->isAdmin());
        $this->assertTrue($activeAdmins->first()->is_active);
    }

    /**
     * ===================================
     * ATTRIBUTE CASTING
     * ===================================
     */

    public function test_is_active_is_cast_to_boolean(): void
    {
        $user = User::factory()->create(['is_active' => 1]);

        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    public function test_last_login_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create(['last_login_at' => now()]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->last_login_at);
    }

    public function test_last_login_at_can_be_null(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);

        $this->assertNull($user->last_login_at);
    }

    public function test_password_is_cast_to_hashed(): void
    {
        // Create a user with a plain password
        $user = User::factory()->create(['password' => 'plainpassword']);

        // The password should be hashed
        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(password_verify('plainpassword', $user->password));
    }

    /**
     * ===================================
     * HIDDEN ATTRIBUTES
     * ===================================
     */

    public function test_password_is_hidden_in_array(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_remember_token_is_hidden_in_array(): void
    {
        $user = User::factory()->create(['remember_token' => 'some-token']);
        $array = $user->toArray();

        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /**
     * ===================================
     * FILLABLE ATTRIBUTES
     * ===================================
     */

    public function test_name_is_fillable(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        $user->update(['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $user->fresh()->name);
    }

    public function test_email_is_fillable(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);
        $user->update(['email' => 'updated@example.com']);

        $this->assertEquals('updated@example.com', $user->fresh()->email);
    }

    public function test_password_is_fillable(): void
    {
        $user = User::factory()->create(['password' => 'original']);
        $user->update(['password' => 'newpassword']);

        $this->assertTrue(password_verify('newpassword', $user->fresh()->password));
    }

    public function test_role_is_fillable(): void
    {
        $user = User::factory()->cashier()->create();
        $user->update(['role' => 'admin']);

        $this->assertEquals('admin', $user->fresh()->role);
    }

    public function test_is_active_is_fillable(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->update(['is_active' => false]);

        $this->assertFalse($user->fresh()->is_active);
    }

    /**
     * ===================================
     * TRAITS
     * ===================================
     */

    public function test_user_has_api_tokens_trait(): void
    {
        $user = User::factory()->create();

        // User should be able to create tokens
        $token = $user->createToken('test');

        $this->assertNotNull($token);
        $this->assertNotEmpty($token->plainTextToken);
    }

    public function test_user_has_factory(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
