<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::firstOrCreate(
            ['document_number' => '0'],
            [
                'name' => 'S/N',
                'document_type' => 'CF',
                'is_default' => true,
                'is_active' => true,
            ]
        );
    }
}
