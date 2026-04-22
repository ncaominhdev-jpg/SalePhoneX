<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    // Lấy danh sách sản phẩm có sẵn tại kho (warehouse_id)
    public function getAvailableProductsByWarehouse($warehouseId)
    {
        $products = DB::table('inventories')
            ->join('product_variants', 'inventories.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'product_variants.id as variant_id',
                'product_variants.name as variant_name',
                'inventories.quantity'
            )
            ->where('inventories.warehouse_id', $warehouseId)
            ->where('inventories.quantity', '>', 0)
            ->get();

        return response()->json($products);
    }
}
