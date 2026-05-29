<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'price' => '200.0000',
                'duration_days' => 30,
                'billing_period' => 'MONTHLY',
                'is_active' => true,
                'features' => [
                    'max_users' => 3,
                    'max_branches' => 1,
                    'max_products' => 1000,
                    'max_warehouses' => 1,
                    'max_pos' => 1,
                    'has_catalog' => false,
                    'max_images_per_product' => 1,
                ],
            ],
            [
                'name' => 'Intermedio',
                'slug' => 'intermedio',
                'price' => '250.0000',
                'duration_days' => 30,
                'billing_period' => 'MONTHLY',
                'is_active' => true,
                'features' => [
                    'max_users' => 6,
                    'max_branches' => 2,
                    'max_products' => 2500,
                    'max_warehouses' => 2,
                    'max_pos' => 2,
                    'has_catalog' => true,
                    'max_images_per_product' => 3,
                ],
            ],
            [
                'name' => 'Profesional',
                'slug' => 'profesional',
                'price' => '350.0000',
                'duration_days' => 30,
                'billing_period' => 'MONTHLY',
                'is_active' => true,
                'features' => [
                    'max_users' => 10,
                    'max_branches' => 3,
                    'max_products' => 5000,
                    'max_warehouses' => 5,
                    'max_pos' => 5,
                    'has_catalog' => true,
                    'max_images_per_product' => 5,
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price' => '800.0000',
                'duration_days' => 30,
                'billing_period' => 'MONTHLY',
                'is_active' => true,
                'features' => [
                    'max_users' => -1,
                    'max_branches' => 10,
                    'max_products' => 10000,
                    'max_warehouses' => -1,
                    'max_pos' => -1,
                    'has_catalog' => true,
                    'max_images_per_product' => 5,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
