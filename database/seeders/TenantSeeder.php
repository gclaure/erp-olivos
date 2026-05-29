<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            $this->command->warn('No se encontró ninguna empresa. Crea una empresa primero.');
            return;
        }

        DB::transaction(function () use ($company) {
            // Actualizar la empresa como tenant activo
            $company->update([
                'status' => TenantStatus::ACTIVE->value,
            ]);

            // Asignar el primer super_admin como owner
            $owner = User::where('is_super_admin', true)->first();
            if ($owner) {
                $company->update(['owner_user_id' => $owner->id]);
            }

            // Asignar tenant_id a todos los usuarios que no lo tienen

            if ($plan && !$company->subscriptions()->exists()) {
                Subscription::create([
                    'plan_id' => $plan->id,
                    'starts_at' => now(),
                    'ends_at' => now()->addYear(),
                    'status' => SubscriptionStatus::ACTIVE,
                    'auto_renew' => false,
                ]);
            }
        });

        $this->command->info("Empresa '{$company->name}' configurada como tenant con suscripción Enterprise.");
    }
}
