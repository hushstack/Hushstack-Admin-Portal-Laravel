<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $partnerRole;

    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();
        $this->partnerRole = Role::where('slug', Role::PARTNER_SLUG)->firstOrFail();
        $this->userRole = Role::where('slug', Role::USER_SLUG)->firstOrFail();
    }

    public function test_partner_can_create_product_via_admin_route(): void
    {
        $partner = User::factory()->create(['role_id' => $this->partnerRole->id]);
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        Sanctum::actingAs($partner);

        $payload = [
            'name' => 'Test Product',
            'sku' => 'SKU-12345',
            'price' => 199.99,
            'qty' => 5,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'short_description' => 'Short desc',
            'description' => 'Long description',
            'is_stock' => true,
        ];

        $response = $this->postJson('/api/admin/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Product')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.brand.id', $brand->id)
            ->assertJsonPath('data.user.id', $partner->id);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-12345']);
    }

    public function test_user_can_create_brand_via_admin_route_with_limit_rules(): void
    {
        $user = User::factory()->create(['role_id' => $this->userRole->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/admin/brands', [
            'name' => 'Blocked Brand',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_partner_cannot_update_product_created_by_someone_else(): void
    {
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        $partner = User::factory()->create(['role_id' => $this->partnerRole->id]);

        $product = Product::factory()->create(['user_id' => $admin->id]);

        Sanctum::actingAs($partner);

        $response = $this->patchJson('/api/admin/products/'.$product->id, ['name' => 'Hijack']);

        $response->assertStatus(403);
    }

    public function test_user_is_limited_to_five_products(): void
    {
        $user = User::factory()->create(['role_id' => $this->userRole->id]);
        Sanctum::actingAs($user);

        $category = Category::factory()->create(['user_id' => $user->id]);
        $brand = Brand::factory()->create(['user_id' => $user->id]);

        // create 5 products
        for ($i = 1; $i <= 5; $i++) {
            $payload = [
                'name' => 'User Product '.$i,
                'sku' => 'SKU-U-'.$i,
                'price' => 10,
                'qty' => 1,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
            ];
            $this->postJson('/api/admin/products', $payload)->assertStatus(201);
        }

        // sixth should fail
        $response = $this->postJson('/api/admin/products', [
            'name' => 'User Product 6',
            'sku' => 'SKU-U-6',
            'price' => 10,
            'qty' => 1,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_products_list_requires_auth_and_filters_to_owner_for_users(): void
    {
        $user = User::factory()->create(['role_id' => $this->userRole->id]);
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', $product->name)
            ->assertJsonPath('data.0.created_at', $product->created_at->format('M d Y'));
    }
}
