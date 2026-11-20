<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. customers (Información de los clientes)
        Schema::create('customers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->nullable();
            $table->string('full_name', 180);
            $table->string('phone', 40);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->index('branch_id');
            $table->index('phone');
            $table->index('full_name');
        });

        // 2. orders (Información general de la reserva)
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('number');
            $table->uuid('customer_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->date('registered_at');
            $table->date('delivery_date');
            $table->string('delivery_time', 30);
            $table->string('delivery_mode', 30)->default('pickup'); // pickup | delivery
            $table->text('delivery_address')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->text('payment_notes')->nullable();
            $table->text('general_notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index('number');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('user_id');
            $table->index('registered_at');
            $table->index('delivery_date');
            $table->index('delivery_mode');
        });

        // 3. order_items (Productos incluidos en el pedido)
        Schema::create('order_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('product_id')->nullable();
            $table->string('product_name', 220);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('portions_or_size', 120)->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->text('custom_description')->nullable(); // Decoración / descripción personalizada
            $table->text('reference_notes')->nullable();    // Ej: TAL CUAL IMAGEN
            $table->text('observations')->nullable();       // Observaciones adicionales
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->index('order_id');
            $table->index('product_id');
        });

        // 4. order_item_details (Características y especificaciones personalizadas)
        Schema::create('order_item_details', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_item_id');
            $table->string('label', 120);
            $table->text('value');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
            $table->index('order_item_id');
        });

        // 5. order_attachments (Fotografías y referencias en formato .webp)
        Schema::create('order_attachments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('order_item_id')->nullable();
            $table->string('file_path', 255);
            $table->string('file_url', 255);
            $table->string('original_name', 255)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
            $table->index('order_id');
            $table->index('order_item_id');
        });

        // 6. order_complements (Complementos y responsables: Cliente / Negocio)
        Schema::create('order_complements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('order_item_id')->nullable();
            $table->string('description', 255);
            $table->string('provider_type', 30)->default('client'); // client | business
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
            $table->index('order_id');
            $table->index('order_item_id');
        });

        // 7. order_payments (Registro de anticipos y pagos parciales)
        Schema::create('order_payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('user_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 60); // QR | Efectivo | Transferencia | Tarjeta
            $table->timestamp('paid_at');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('order_id');
            $table->index('user_id');
            $table->index('paid_at');
        });

        // 8. Registrar permiso manage-orders y sincronizar con roles Admin y Super Admin
        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            Permission::firstOrCreate([
                'name' => 'manage-orders',
                'guard_name' => 'web',
            ]);

            $roles = Role::whereIn('name', [
                'Admin',
                'Administrador',
                'admin',
                'administrador',
                'Super Admin',
                'Super-admin',
                'super-admin',
                'super_admin',
            ])->get();

            foreach ($roles as $role) {
                if (!$role->hasPermissionTo('manage-orders')) {
                    $role->givePermissionTo('manage-orders');
                }
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_complements');
        Schema::dropIfExists('order_attachments');
        Schema::dropIfExists('order_item_details');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
    }
};

