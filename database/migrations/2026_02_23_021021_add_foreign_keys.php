<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // branches -> companies
        Schema::table('branches', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        // warehouses -> branches
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });

        // products -> categories
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });

        // point_of_sales -> warehouses
        Schema::table('point_of_sales', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
        });

        // purchase_orders -> providers, warehouses, users
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreign('provider_id')->references('id')->on('providers')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // purchase_order_details -> purchase_orders, products
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // purchases -> providers, warehouses, users
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign('provider_id')->references('id')->on('providers')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // purchase_details -> purchases, products
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->foreign('purchase_id')->references('id')->on('purchases')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // quotations -> clients, users
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // quotation_details -> quotations, products
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->foreign('quotation_id')->references('id')->on('quotations')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // sales -> clients, warehouses, users
        Schema::table('sales', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // sale_details -> sales, products
        Schema::table('sale_details', function (Blueprint $table) {
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // movements -> warehouses, users
        Schema::table('movements', function (Blueprint $table) {
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // movement_details -> movements, products
        Schema::table('movement_details', function (Blueprint $table) {
            $table->foreign('movement_id')->references('id')->on('movements')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // transfers -> warehouses (from/to), users
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // transfer_details -> transfers, products
        Schema::table('transfer_details', function (Blueprint $table) {
            $table->foreign('transfer_id')->references('id')->on('transfers')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // kardexes -> products, warehouses, users
        Schema::table('kardexes', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // stocks -> products, warehouses
        Schema::table('stocks', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $tables = [
            'branches' => ['company_id'],
            'warehouses' => ['branch_id'],
            'products' => ['category_id'],
            'point_of_sales' => ['warehouse_id'],
            'purchase_orders' => ['provider_id', 'warehouse_id', 'user_id'],
            'purchase_order_details' => ['purchase_order_id', 'product_id'],
            'purchases' => ['provider_id', 'warehouse_id', 'user_id'],
            'purchase_details' => ['purchase_id', 'product_id'],
            'quotations' => ['client_id', 'user_id'],
            'quotation_details' => ['quotation_id', 'product_id'],
            'sales' => ['client_id', 'warehouse_id', 'user_id'],
            'sale_details' => ['sale_id', 'product_id'],
            'movements' => ['warehouse_id', 'user_id'],
            'movement_details' => ['movement_id', 'product_id'],
            'transfers' => ['from_warehouse_id', 'to_warehouse_id', 'user_id'],
            'transfer_details' => ['transfer_id', 'product_id'],
            'kardexes' => ['product_id', 'warehouse_id', 'user_id'],
            'stocks' => ['product_id', 'warehouse_id'],
        ];

        foreach ($tables as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->dropForeign([$column]);
                }
            });
        }
    }
};
