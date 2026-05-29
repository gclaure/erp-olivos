<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EcommerceSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            \App\Models\EcommerceSetting::firstOrCreate([],
                [
                    'store_name' => $company->name,
                    'store_slogan' => 'Artículos de Calidad',
                    'store_description' => 'Bienvenidos a nuestra tienda en línea.',
                    'primary_color' => '#4f46e5',
                    'secondary_color' => '#0f172a',
                    'is_catalog_active' => true,
                    'mostrar_precios' => true,
                    'mostrar_inventario' => true,
                ]
            );
        }
    }
}
