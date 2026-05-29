<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $search = $request->get('search');

        $products = Product::query()
            ->where('is_active', true)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('code', 'ilike', "%{$search}%");
                });
            })
            ->limit(20)
            ->get()
            ->map(fn($product) => [
                'id'   => $product->id,
                'name' => $product->name,
                'code' => $product->code,
            ]);

        return response()->json($products);
    }
}
