<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');

        $products = [
            ['code' => 'LAP-001', 'name' => 'Laptop HP Pavilion 15', 'category' => 'Computación', 'price' => 4500.00],
            ['code' => 'LAP-002', 'name' => 'Laptop Dell Inspiron 14', 'category' => 'Computación', 'price' => 5200.00],
            ['code' => 'MON-001', 'name' => 'Monitor Samsung 24" FHD', 'category' => 'Computación', 'price' => 1200.00],
            ['code' => 'TEC-001', 'name' => 'Teclado Mecánico Logitech G413', 'category' => 'Accesorios', 'price' => 450.00],
            ['code' => 'MOU-001', 'name' => 'Mouse Logitech MX Master 3', 'category' => 'Accesorios', 'price' => 380.00],
            ['code' => 'CEL-001', 'name' => 'Samsung Galaxy A54', 'category' => 'Telefonía', 'price' => 2100.00],
            ['code' => 'CEL-002', 'name' => 'Xiaomi Redmi Note 13', 'category' => 'Telefonía', 'price' => 1500.00],
            ['code' => 'AUD-001', 'name' => 'Audífonos Sony WH-1000XM5', 'category' => 'Audio y Video', 'price' => 1800.00],
            ['code' => 'PAR-001', 'name' => 'Parlante JBL Flip 6', 'category' => 'Audio y Video', 'price' => 650.00],
            ['code' => 'SSD-001', 'name' => 'SSD Samsung 970 EVO 1TB', 'category' => 'Almacenamiento', 'price' => 550.00],
            ['code' => 'HDD-001', 'name' => 'Disco Duro Seagate 2TB', 'category' => 'Almacenamiento', 'price' => 380.00],
            ['code' => 'USB-001', 'name' => 'Memoria USB Kingston 64GB', 'category' => 'Almacenamiento', 'price' => 55.00],
            ['code' => 'ROU-001', 'name' => 'Router TP-Link Archer AX21', 'category' => 'Redes', 'price' => 420.00],
            ['code' => 'SWI-001', 'name' => 'Switch TP-Link 8 puertos', 'category' => 'Redes', 'price' => 180.00],
            ['code' => 'IMP-001', 'name' => 'Impresora Epson L3250', 'category' => 'Impresión', 'price' => 1350.00],
            ['code' => 'TON-001', 'name' => 'Tóner HP 107A Negro', 'category' => 'Impresión', 'price' => 280.00],
            ['code' => 'CAB-001', 'name' => 'Cable HDMI 2.1 3m', 'category' => 'Cables y Adaptadores', 'price' => 65.00],
            ['code' => 'CAB-002', 'name' => 'Adaptador USB-C a HDMI', 'category' => 'Cables y Adaptadores', 'price' => 120.00],
            ['code' => 'SW-001', 'name' => 'Licencia Microsoft Office 365', 'category' => 'Software', 'price' => 500.00],
            ['code' => 'SW-002', 'name' => 'Antivirus ESET NOD32 1 año', 'category' => 'Software', 'price' => 180.00],
        ];

        foreach ($products as $index => $product) {
            // Generating dummy location and brand for the seed
            $dummyLocation = strtoupper(chr(65 + ($index % 26))) . '-' . rand(100, 999) . '-' . str_pad((string)rand(1, 99), 2, '0', STR_PAD_LEFT);
            $brands = ['HP', 'Dell', 'Samsung', 'Logitech', 'Sony', 'Xiaomi', 'TP-Link', 'Epson', 'Kingston'];
            $dummyBrand = $brands[array_rand($brands)];
            
            Product::firstOrCreate(
                ['code' => $product['code']],
                [
                    'code' => $product['code'],
                    'name' => $product['name'],
                    'category_id' => $categories[$product['category']] ?? null,
                    'price' => $product['price'],
                    'is_active' => true,
                    'location' => $dummyLocation,
                    'brand' => $dummyBrand,
                    'observations' => 'Producto generado por seeder.',
                    'reference_link' => 'https://example.com/producto/' . strtolower($product['code']),
                ]
            );
        }
    }
}
