<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electrónicos', 'description' => 'Dispositivos y equipos electrónicos', 'is_active' => true],
            ['name' => 'Computación', 'description' => 'Computadoras, periféricos y accesorios', 'is_active' => true],
            ['name' => 'Redes', 'description' => 'Equipos de networking y conectividad', 'is_active' => true],
            ['name' => 'Telefonía', 'description' => 'Teléfonos móviles y accesorios', 'is_active' => true],
            ['name' => 'Audio y Video', 'description' => 'Equipos de sonido, parlantes y video', 'is_active' => true],
            ['name' => 'Almacenamiento', 'description' => 'Discos duros, SSDs y memorias', 'is_active' => true],
            ['name' => 'Impresión', 'description' => 'Impresoras, tóners y suministros', 'is_active' => true],
            ['name' => 'Software', 'description' => 'Licencias y suscripciones de software', 'is_active' => true],
            ['name' => 'Cables y Adaptadores', 'description' => 'Cables, conectores y adaptadores', 'is_active' => true],
            ['name' => 'Accesorios', 'description' => 'Accesorios generales y misceláneos', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
