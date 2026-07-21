<?php

namespace Tests\Feature\Sales;

use App\Models\Article;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleStoreEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_is_rejected_when_stock_is_zero(): void
    {
        [$tenant, $branch, $user] = $this->makeTenantBranchAndUser();
        $article = $this->makeArticle($tenant);

        ArticleBranchStock::create([
            'article_id' => $article->id,
            'branch_id' => $branch->id,
            'quantity' => 0,
        ]);

        $payload = $this->validPayload($branch->id, $article->id, 1, 1000, [
            ['method' => 'cash', 'amount' => 1000],
        ]);

        $response = $this->actingAs($user)->from('/sales/new')->post(route('sales.store'), $payload);

        $response->assertRedirect('/sales/new');
        $response->assertSessionHasErrors('items.0.quantity');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_sale_with_exact_stock_succeeds_and_sets_stock_to_zero(): void
    {
        [$tenant, $branch, $user] = $this->makeTenantBranchAndUser();
        $article = $this->makeArticle($tenant);

        ArticleBranchStock::create([
            'article_id' => $article->id,
            'branch_id' => $branch->id,
            'quantity' => 2,
        ]);

        $payload = $this->validPayload($branch->id, $article->id, 2, 1500, [
            ['method' => 'cash', 'amount' => 3000],
        ]);

        $response = $this->actingAs($user)->post(route('sales.store'), $payload);

        $sale = Sale::first();
        $response->assertRedirect(route('sales.receipt', $sale));
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('article_branch_stock', [
            'article_id' => $article->id,
            'branch_id' => $branch->id,
            'quantity' => 0,
        ]);
    }

    public function test_sale_is_forbidden_when_user_tries_another_branch(): void
    {
        [$tenant, $branchA, $user] = $this->makeTenantBranchAndUser();
        $branchB = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Succursale B',
            'is_active' => true,
        ]);

        $article = $this->makeArticle($tenant);

        ArticleBranchStock::create([
            'article_id' => $article->id,
            'branch_id' => $branchB->id,
            'quantity' => 5,
        ]);

        $payload = $this->validPayload($branchB->id, $article->id, 1, 1200, [
            ['method' => 'cash', 'amount' => 1200],
        ]);

        $response = $this->actingAs($user)->post(route('sales.store'), $payload);

        $response->assertForbidden();
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_partial_payment_without_credit_sets_partial_status(): void
    {
        [$tenant, $branch, $user] = $this->makeTenantBranchAndUser();
        $article = $this->makeArticle($tenant);

        ArticleBranchStock::create([
            'article_id' => $article->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
        ]);

        $payload = $this->validPayload($branch->id, $article->id, 2, 1000, [
            ['method' => 'cash', 'amount' => 500],
        ]);

        $this->actingAs($user)->post(route('sales.store'), $payload);

        $sale = Sale::first();
        $this->assertSame('partial', $sale->payment_status);
    }

    public function test_credit_payment_requires_customer_and_sets_credit_status(): void
    {
        [$tenant, $branch, $user] = $this->makeTenantBranchAndUser();
        $article = $this->makeArticle($tenant);

        ArticleBranchStock::create([
            'article_id' => $article->id,
            'branch_id' => $branch->id,
            'quantity' => 10,
        ]);

        $missingCustomerPayload = $this->validPayload($branch->id, $article->id, 2, 1000, [
            ['method' => 'credit', 'amount' => 0],
        ]);

        $response = $this->actingAs($user)
            ->from('/sales/new')
            ->post(route('sales.store'), $missingCustomerPayload);

        $response->assertRedirect('/sales/new');
        $response->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('sales', 0);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Client Test',
            'credit_limit' => 100000,
            'credit_balance' => 0,
        ]);

        $validCreditPayload = $this->validPayload($branch->id, $article->id, 2, 1000, [
            ['method' => 'credit', 'amount' => 0],
        ]);
        $validCreditPayload['customer_id'] = $customer->id;

        $this->actingAs($user)->post(route('sales.store'), $validCreditPayload);

        $sale = Sale::latest('id')->first();
        $this->assertSame('credit', $sale->payment_status);
        $this->assertEquals(2000.0, (float) $customer->fresh()->credit_balance);
    }

    private function makeTenantBranchAndUser(): array
    {
        $tenant = Tenant::create([
            'shop_name' => 'Shop Test',
            'slug' => 'shop-test-'.uniqid(),
            'status' => 'active',
        ]);

        app()->instance('currentTenant', $tenant);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Succursale A',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'is_super_admin' => false,
        ]);

        return [$tenant, $branch, $user];
    }

    private function makeArticle(Tenant $tenant): Article
    {
        return Article::create([
            'tenant_id' => $tenant->id,
            'designation' => 'Article Test',
            'reference' => 'REF-'.uniqid(),
            'unit' => 'piece',
            'sale_price_ht' => 1000,
            'tax_rate' => 0,
            'sale_price_ttc' => 1000,
            'is_active' => true,
        ]);
    }

    private function validPayload(int $branchId, int $articleId, int $quantity, float $unitPrice, array $payments): array
    {
        return [
            'branch_id' => $branchId,
            'items' => [
                [
                    'article_id' => $articleId,
                    'quantity' => $quantity,
                    'unit_price_ttc' => $unitPrice,
                    'discount_amount' => 0,
                ],
            ],
            'payment_methods' => $payments,
            'notes' => 'test',
        ];
    }
}
