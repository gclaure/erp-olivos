<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();

        if ($company) {
            Branch::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'Casa Matriz',
                ],
                [
                    'address' => 'Av. Principal #123, Santa Cruz, Bolivia',
                    'phone' => '+591 70000000',
                    'whatsapp_number' => '59172281455',
                    'is_main' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
