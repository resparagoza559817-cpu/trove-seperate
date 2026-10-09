<?php

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\Site;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    // SQLite can't drop the legacy deliveries FK that the MySQL migration removes, so stub the table.
    \Illuminate\Support\Facades\Schema::create('deliveries', fn ($t) => $t->id());
    $this->matina  = Site::create(['site_name' => 'Matina Commissary']);
    $this->jacinto = Site::create(['site_name' => 'Jacinto Branch']);
    $this->owner = User::create([
        'first_name' => 'T', 'last_name' => 'O', 'email' => 'o@t.com',
        'password' => bcrypt('x'), 'role' => 'Owner', 'site_id' => $this->matina->id,
    ]);
    $this->flour = Inventory::create([
        'item_name' => 'Flour', 'category' => 'Baking Essentials', 'unit' => 'kg',
        'quantity_on_hand' => 20, 'minimum_stock' => 1,
    ]);
    $this->box = Inventory::create([
        'item_name' => 'Box', 'category' => 'Packaging', 'unit' => 'pcs',
        'quantity_on_hand' => 7, 'minimum_stock' => 1,
    ]);
});

function payload(array $over = []): array
{
    return array_merge([
        'product_name' => 'Test Cake', 'category' => 'Cake', 'price' => 100,
        'stock_quantity' => 0,
        'recipe' => [['inventory_id' => test()->flour->id, 'quantity_needed' => 2]],
    ], $over);
}

it('maxProducibleFrom uses the scarcest ingredient', function () {
    // 20 kg flour / 2 kg = 10, 7 boxes / 1 = 7  => 7
    expect(Product::maxProducibleFrom([
        ['available' => 20, 'needed' => 2],
        ['available' => 7,  'needed' => 1],
    ]))->toBe(7);
    expect(Product::maxProducibleFrom([]))->toBeNull();
    expect(Product::maxProducibleFrom([['available' => 0.3, 'needed' => 0.1]]))->toBe(3); // float safe
});

it('rejects a product with no recipe', function () {
    $this->actingAs($this->owner)
        ->post('/products', payload(['recipe' => []]))
        ->assertSessionHasErrors('recipe');
    expect(Product::count())->toBe(0);
});

it('rejects duplicate ingredients and missing quantity', function () {
    $f = $this->flour->id;
    $this->actingAs($this->owner)->post('/products', payload(['recipe' => [
        ['inventory_id' => $f, 'quantity_needed' => 1], ['inventory_id' => $f, 'quantity_needed' => 1],
    ]]))->assertSessionHasErrors('recipe.0.inventory_id');
    $this->actingAs($this->owner)->post('/products', payload(['recipe' => [['inventory_id' => $f]]]))
        ->assertSessionHasErrors('recipe.0.quantity_needed');
});

it('creates at Matina, ignores any posted site, and deducts ingredients for starting stock', function () {
    $this->actingAs($this->owner)
        ->post('/products', payload(['stock_quantity' => 4, 'site_id' => $this->jacinto->id]))
        ->assertSessionHasNoErrors()->assertSessionMissing('error')->assertRedirect('/products');

    $p = Product::first();
    expect($p->site_id)->toBe($this->matina->id)
        ->and($p->stock_quantity)->toBe(4)
        ->and((float) $p->materials->first()->pivot->quantity_needed)->toBe(2.0)
        ->and($p->maxProducible())->toBe(6);                     // 12 kg left / 2
    expect((float) $this->flour->fresh()->quantity_on_hand)->toBe(12.0);
    expect(InventoryLog::where('type', 'used')->first()->quantity)->toEqual(8);
});

it('blocks starting stock above what the raw materials can make', function () {
    $this->actingAs($this->owner)
        ->post('/products', payload(['stock_quantity' => 11])) // max is 10
        ->assertSessionHas('error');
    expect(Product::count())->toBe(0);
    expect((float) $this->flour->fresh()->quantity_on_hand)->toBe(20.0);
});

it('accepts a brand new category', function () {
    $this->actingAs($this->owner)
        ->post('/products', payload(['category' => '__new__', 'new_category' => 'Bread']));
    expect(Product::first()->category)->toBe('Bread');
});

it('renders the create form without a site field and with a recipe row', function () {
    $this->actingAs($this->owner)->get('/products/create')
        ->assertOk()->assertDontSee('name="site_id"', false)
        ->assertSee('Starting Finished Stock')->assertSee('+ Add new category...', false);
    $this->actingAs($this->owner)->get('/inventory/create')->assertOk()->assertDontSee('name="site_id"', false);
});

it('stores inventory at Matina', function () {
    $this->actingAs($this->owner)->post('/inventory', [
        'item_name' => 'Sugar', 'category' => 'Other', 'unit' => 'kg',
        'quantity_on_hand' => 5, 'minimum_stock' => 1,
    ])->assertRedirect('/inventory');
    expect(Inventory::where('item_name', 'Sugar')->first()->site_id)->toBe($this->matina->id);
});

it('branch transfer form fixes Matina as source and lists only other sites as destination', function () {
    $this->actingAs($this->owner)->get('/branch-transfers/create')->assertOk()
        ->assertSee('Jacinto Branch')->assertSee('readonly', false);
});

it('requires a destination and rejects destination = source on transfer', function () {
    $p = Product::create(['product_name' => 'X', 'price' => 1, 'stock_quantity' => 5, 'status' => 'active']);
    $row = ['items' => [['product_id' => $p->id, 'qty_sent' => 1, 'unit_price' => 1]], 'batch_date' => '2026-10-09'];
    $this->actingAs($this->owner)->post('/branch-transfers', $row + ['source_site_id' => $this->matina->id])
        ->assertSessionHasErrors('destination_site_id');
    $this->actingAs($this->owner)->post('/branch-transfers', $row + [
        'source_site_id' => $this->matina->id, 'destination_site_id' => $this->matina->id,
    ])->assertSessionHasErrors('destination_site_id');
    $this->actingAs($this->owner)->post('/branch-transfers', $row + [
        'source_site_id' => $this->matina->id, 'destination_site_id' => $this->jacinto->id,
    ])->assertRedirect();
    expect($p->fresh()->stock_quantity)->toBe(4);
});

it('edit and show pages render and edit has no site field', function () {
    $this->actingAs($this->owner)->post('/products', payload());
    $p = Product::first();
    $this->actingAs($this->owner)->get("/products/{$p->id}/edit")->assertOk()->assertDontSee('name="site_id"', false)->assertSee('Flour');
    $this->actingAs($this->owner)->get("/products/{$p->id}")->assertOk();
    $this->actingAs($this->owner)->get('/products')->assertOk();
    $this->actingAs($this->owner)->get('/dashboard')->assertOk();
    $this->actingAs($this->owner)->put("/products/{$p->id}", ['product_name' => 'Test Cake', 'category' => 'Cake', 'price' => 120, 'status' => 'active'])
        ->assertRedirect('/products');
});
