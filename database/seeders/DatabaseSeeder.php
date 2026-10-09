<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Site;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        //  SITES
        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $matina = Site::firstOrCreate(
            ['site_name' => 'Matina Commissary'],
            ['street' => 'McArthur Highway', 'city' => 'Davao City']
        );

        $jacinto = Site::firstOrCreate(
            ['site_name' => 'Jacinto Branch'],
            ['street' => 'C.M. Recto Ave', 'city' => 'Davao City']
        );

        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        //  USERS  (login: owner@trove.com / password)
        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        User::firstOrCreate(
            ['email' => 'owner@trove.com'],
            [
                'first_name' => 'Trove',
                'last_name'  => 'Owner',
                'mobile_no'  => '09170000001',
                'password'   => Hash::make('password'),
                'role'       => 'Owner',
                'site_id'    => $matina->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager@trove.com'],
            [
                'first_name' => 'Trove',
                'last_name'  => 'Manager',
                'mobile_no'  => '09170000002',
                'password'   => Hash::make('password'),
                'role'       => 'Manager',
                'site_id'    => $matina->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@trove.com'],
            [
                'first_name' => 'Trove',
                'last_name'  => 'Staff',
                'mobile_no'  => '09170000003',
                'password'   => Hash::make('password'),
                'role'       => 'Staff',
                'site_id'    => $jacinto->id,
            ]
        );

        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        //  INVENTORY  (raw materials / ingredients)
        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $ingredients = [
            ['item_name' => 'All-Purpose Flour', 'category' => 'Baking Essentials',     'unit' => 'kg',   'qty' => 50,  'min' => 10],
            ['item_name' => 'White Sugar',       'category' => 'Baking Essentials',     'unit' => 'kg',   'qty' => 40,  'min' => 10],
            ['item_name' => 'Eggs',              'category' => 'Dairy & Eggs',          'unit' => 'dozen','qty' => 30,  'min' => 5],
            ['item_name' => 'Butter',            'category' => 'Dairy & Eggs',          'unit' => 'kg',   'qty' => 25,  'min' => 5],
            ['item_name' => 'Cocoa Powder',      'category' => 'Flavoring & Fillings',  'unit' => 'kg',   'qty' => 15,  'min' => 3],
            ['item_name' => 'Ube Halaya',        'category' => 'Flavoring & Fillings',  'unit' => 'kg',   'qty' => 12,  'min' => 3],
            ['item_name' => 'Cream Cheese',      'category' => 'Dairy & Eggs',          'unit' => 'kg',   'qty' => 18,  'min' => 4],
            ['item_name' => 'Ripe Bananas',      'category' => 'Flavoring & Fillings',  'unit' => 'kg',   'qty' => 20,  'min' => 5],
            ['item_name' => 'Whipping Cream',    'category' => 'Dairy & Eggs',          'unit' => 'liters','qty' => 15, 'min' => 3],
            ['item_name' => 'Cake Boxes',        'category' => 'Packaging',             'unit' => 'pcs',  'qty' => 100, 'min' => 20],
        ];

        $inv = [];
        foreach ($ingredients as $i) {
            $inv[$i['item_name']] = Inventory::firstOrCreate(
                ['item_name' => $i['item_name']],
                [
                    'category'         => $i['category'],
                    'unit'             => $i['unit'],
                    'quantity_on_hand' => $i['qty'],
                    'quantity_damaged' => 0,
                    'minimum_stock'    => $i['min'],
                    'site_id'          => $matina->id,
                ]
            );
        }

        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        //  PRODUCTS + RECIPES  (from the batch notes)
        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $products = [
            [
                'product_name' => 'Chocolate Cake',
                'category'     => 'Cake',
                'price'        => 480.00,
                'recipe'       => [
                    'All-Purpose Flour' => 0.5,
                    'White Sugar'       => 0.4,
                    'Eggs'              => 0.5,
                    'Butter'            => 0.3,
                    'Cocoa Powder'      => 0.25,
                    'Cake Boxes'        => 1,
                ],
            ],
            [
                'product_name' => 'Banana Cake',
                'category'     => 'Cake',
                'price'        => 150.00,
                'recipe'       => [
                    'All-Purpose Flour' => 0.3,
                    'White Sugar'       => 0.2,
                    'Eggs'              => 0.25,
                    'Ripe Bananas'      => 0.4,
                    'Cake Boxes'        => 1,
                ],
            ],
            [
                'product_name' => 'Ube Cake',
                'category'     => 'Cake',
                'price'        => 490.00,
                'recipe'       => [
                    'All-Purpose Flour' => 0.5,
                    'White Sugar'       => 0.4,
                    'Eggs'              => 0.5,
                    'Ube Halaya'        => 0.4,
                    'Cake Boxes'        => 1,
                ],
            ],
            [
                'product_name' => 'Cheese Cake',
                'category'     => 'Cake',
                'price'        => 500.00,
                'recipe'       => [
                    'Cream Cheese'   => 0.5,
                    'White Sugar'    => 0.3,
                    'Eggs'           => 0.5,
                    'Butter'         => 0.2,
                    'Cake Boxes'     => 1,
                ],
            ],
            [
                'product_name' => 'Cotton Cake',
                'category'     => 'Cake',
                'price'        => 550.00,
                'recipe'       => [
                    'All-Purpose Flour' => 0.4,
                    'White Sugar'       => 0.35,
                    'Eggs'              => 0.6,
                    'Whipping Cream'    => 0.3,
                    'Cake Boxes'        => 1,
                ],
            ],
        ];

        foreach ($products as $p) {
            $product = Product::firstOrCreate(
                ['product_name' => $p['product_name']],
                [
                    'category' => $p['category'],
                    'price'    => $p['price'],
                    'status'   => 'active',
                    'site_id'  => $matina->id,
                ]
            );

            // Attach recipe (materials) if not already attached
            if ($product->materials()->count() === 0) {
                $attach = [];
                foreach ($p['recipe'] as $itemName => $qtyUsed) {
                    if (isset($inv[$itemName])) {
                        $attach[$inv[$itemName]->id] = ['quantity_needed' => $qtyUsed];
                    }
                }
                $product->materials()->attach($attach);
            }
        }
    }
}