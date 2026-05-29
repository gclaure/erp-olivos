<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Checking for duplicates in aggregation...\n";
    $duplicates = DB::table('sale_details')
        ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
        ->where('sales.is_active', true)
        ->select([
            DB::raw('CAST(sales.date AS DATE) as d'),
            'sales.branch_id as b',
            'sales.warehouse_id as w',
            'sale_details.product_id as p',
            DB::raw('COUNT(*) as cnt')
        ])
        ->groupBy('d', 'b', 'w', 'p')
        ->having(DB::raw('COUNT(*)'), '>', 1)
        ->get();
    
    if ($duplicates->count() > 0) {
        echo "FOUND DUPLICATES IN AGGREGATION:\n";
        print_r($duplicates->toArray());
    } else {
        echo "No duplicates found in aggregation result set.\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getSql')) {
        echo "SQL: " . $e->getSql() . "\n";
    }
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
