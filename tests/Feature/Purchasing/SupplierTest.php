<?php

namespace Tests\Feature\Purchasing;

use App\Modules\Auth\Models\User;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    protected string $endpoint = '/api/suppliers';

    public function test_authenticated_user_can_list_suppliers(): void
    {
        Supplier::factory()->count(5)->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_list_filter_search(): void
    {
        Supplier::factory()->create(['name' => 'Alpha Supply']);
        Supplier::factory()->create(['name' => 'Beta Corp']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson($this->endpoint . '?search=Alpha');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Supply');
    }

    public function test_admin_can_create_supplier(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $data = [
            'name' => 'New Supplier',
            'contact_person' => 'John Doe',
            'email' => 'john@supplier.com',
            'phone' => '1234567890',
            'address' => '123 Supplier St',
        ];

        $response = $this->postJson($this->endpoint, $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Supplier');

        $this->assertDatabaseHas('suppliers', ['email' => 'john@supplier.com']);
    }

    public function test_create_validation(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_update_supplier(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Old Name']);
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->putJson("{$this->endpoint}/{$supplier->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertEquals('Updated Name', $supplier->fresh()->name);
    }

    public function test_admin_can_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->deleteJson("{$this->endpoint}/{$supplier->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_cannot_delete_supplier_with_purchase_orders(): void
    {
        $supplier = Supplier::factory()->create();
        PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->deleteJson("{$this->endpoint}/{$supplier->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete supplier with existing purchase orders.']);
    }
}
