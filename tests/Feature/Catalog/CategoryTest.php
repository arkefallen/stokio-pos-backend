<?php

namespace Tests\Feature\Catalog;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected string $endpoint = '/api/categories';

    /**
     * ===================================
     * INDEX (LIST) SCENARIOS
     * ===================================
     */

    public function test_authenticated_user_can_list_categories(): void
    {
        Category::factory()->count(3)->create();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_can_filter_active_categories(): void
    {
        Category::factory()->count(2)->create(['is_active' => true]);
        Category::factory()->inactive()->create();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?active=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_can_include_products_count(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?with_count=1');

        $response->assertStatus(200)
            ->assertJsonFragment(['products_count' => 1]);
    }

    /**
     * ===================================
     * STORE (CREATE) SCENARIOS
     * ===================================
     */

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson($this->endpoint, [
            'name' => 'New Category',
            'description' => 'Description',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Category');

        $this->assertDatabaseHas('categories', ['name' => 'New Category']);
    }

    public function test_manager_can_create_category(): void
    {
        $manager = User::factory()->manager()->create();
        Sanctum::actingAs($manager);

        $response = $this->postJson($this->endpoint, [
            'name' => 'New Category',
        ]);

        $response->assertStatus(201);
    }

    public function test_cashier_cannot_create_category(): void
    {
        $cashier = User::factory()->cashier()->create();
        Sanctum::actingAs($cashier);

        $response = $this->postJson($this->endpoint, [
            'name' => 'New Category',
        ]);

        $response->assertStatus(403);
    }

    public function test_create_validation(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * ===================================
     * UPDATE SCENARIOS
     * ===================================
     */

    public function test_admin_can_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->putJson("{$this->endpoint}/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Name']);
    }

    public function test_cashier_cannot_update_category(): void
    {
        $category = Category::factory()->create();
        $cashier = User::factory()->cashier()->create();
        Sanctum::actingAs($cashier);

        $response = $this->putJson("{$this->endpoint}/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(403);
    }

    /**
     * ===================================
     * DELETE SCENARIOS
     * ===================================
     */

    public function test_admin_can_delete_category(): void
    {
        $category = Category::factory()->create();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->deleteJson("{$this->endpoint}/{$category->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_with_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->deleteJson("{$this->endpoint}/{$category->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete category with existing products.']);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_cashier_cannot_delete_category(): void
    {
        $category = Category::factory()->create();
        $cashier = User::factory()->cashier()->create();
        Sanctum::actingAs($cashier);

        $response = $this->deleteJson("{$this->endpoint}/{$category->id}");

        $response->assertStatus(403);
    }
}
