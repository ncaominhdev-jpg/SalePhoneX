<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // Lấy danh sách sản phẩm
    public function index(Request $request)
    {
        $products = Product::query();

        if ($request->has('name')) {
            $products->where('name', 'like', '%' . $request->name . '%');
        }

        $products = $products->get();

        // Thêm tiền tố đường dẫn ảnh để React có thể hiển thị đúng
        $products->transform(function ($product) {
            if ($product->image) {
                $product->image = url('storage/' . $product->image);
            }
            return $product;
        });

        return response()->json($products);
    }

    // Lấy sản phẩm theo danh mục
    public function getByCategory($category_id)
    {
        $products = Product::where('category_id', $category_id)->get();

        return response()->json($products);
    }

    // Lấy sản phẩm theo brand
    public function getByBrand($brand_id)
    {
        $products = Product::where('brands_id', $brand_id)->get();

        // Thêm tiền tố đường dẫn ảnh để React có thể hiển thị đúng
        $products->transform(function ($product) {
            if ($product->image) {
                $product->image = url('storage/' . $product->image);
            }
            return $product;
        });

        return response()->json($products);
    }



    // Tạo mới sản phẩm
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'image' => 'nullable|string',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'brands_id' => 'required|integer|exists:brands,id',
            'attributeValues' => 'nullable|array',
            'attributeValues.*.attribute_id' => 'required_with:attributeValues|integer|exists:attributes,id',
            'attributeValues.*.value' => 'required_with:attributeValues|string|max:255',
        ]);

        $product = Product::create($validated);

        // Xử lý lưu các thuộc tính
        if (isset($validated['attributeValues'])) {
            foreach ($validated['attributeValues'] as $attrVal) {
                $product->attributeValues()->create([
                    'attribute_id' => $attrVal['attribute_id'],
                    'value' => $attrVal['value'],
                ]);
            }
        }

        return response()->json($product, 201);
    }


    // Xem chi tiết sản phẩm
    public function show($id)
    {
        $product = Product::with(['productVariants', 'media'])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }

        // Thêm tiền tố cho ảnh chính
        if ($product->image) {
            $product->image = url('storage/' . $product->image);
        }

        // Thêm tiền tố cho các ảnh phụ
        $product->media->transform(function ($media) {
            $media->url = url('storage/' . $media->url);
            return $media;
        });

        // ✅ Sửa đường dẫn ảnh trong description (nếu có)
        if ($product->description) {
            $product->description = preg_replace_callback(
                '/src="([^"]+)"/i',
                function ($matches) {
                    $src = $matches[1];
                    // Nếu chưa có http thì thêm tiền tố domain
                    if (!preg_match('/^http(s)?:\/\//', $src)) {
                        $src = url($src);
                    }
                    return 'src="' . $src . '"';
                },
                $product->description
            );
        }

        return response()->json($product);
    }



    // Cập nhật sản phẩm
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric',
            'image' => 'nullable|string',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'brands_id' => 'sometimes|integer|exists:brands,id',
            'attributeValues' => 'nullable|array',
            'attributeValues.*.attribute_id' => 'required_with:attributeValues|integer|exists:attributes,id',
            'attributeValues.*.value' => 'required_with:attributeValues|string|max:255',
        ]);

        $product->update($validated);

        // Xử lý cập nhật các thuộc tính
        if (isset($validated['attributeValues'])) {
            // Xóa các thuộc tính cũ
            $product->attributeValues()->delete();

            // Thêm các thuộc tính mới
            foreach ($validated['attributeValues'] as $attrVal) {
                $product->attributeValues()->create([
                    'attribute_id' => $attrVal['attribute_id'],
                    'value' => $attrVal['value'],
                ]);
            }
        }

        return response()->json($product);
    }

    // Xóa sản phẩm
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $product->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // Lọc sản phẩm theo brand và category
    public function filterByBrandAndCategory($brand_id, $category_id)
    {
        $products = DB::table('products')
            ->join('brand_category', 'products.brands_id', '=', 'brand_category.brand_id')
            ->where('products.brands_id', $brand_id)
            ->where('brand_category.category_id', $category_id)
            ->select('products.*')
            ->get();

        // Thêm tiền tố đường dẫn ảnh để React có thể hiển thị đúng
        $products->transform(function ($product) {
            if ($product->image) {
                $product->image = url('storage/' . $product->image);
            }
            return $product;
        });

        return response()->json($products);
    }

    // Lọc sản phẩm theo thuộc tính
    public function filterByAttributes(Request $request)
    {
        $attributeFilters = $request->input('attributes'); // expecting array of ['attribute_id' => value]

        if (empty($attributeFilters) || !is_array($attributeFilters)) {
            return response()->json(['message' => 'Invalid attributes filter'], 400);
        }

        $query = Product::query();

        // Nếu chỉ có 1 attribute thì lọc theo attribute đó
        if (count($attributeFilters) === 1) {
            foreach ($attributeFilters as $attributeId => $value) {
                $query->whereHas('attributeValues', function ($q) use ($attributeId, $value) {
                    $q->where('attribute_id', $attributeId)
                        ->where('value', $value);
                });
            }
        } else {
            // Nếu có nhiều attribute, lọc theo OR (ít nhất 1 attribute đúng)
            $query->whereHas('attributeValues', function ($q) use ($attributeFilters) {
                $q->where(function ($q2) use ($attributeFilters) {
                    foreach ($attributeFilters as $attributeId => $value) {
                        $q2->orWhere(function ($q3) use ($attributeId, $value) {
                            $q3->where('attribute_id', $attributeId)
                                ->where('value', $value);
                        });
                    }
                });
            });
        }

        $products = $query->get();

        // Thêm tiền tố đường dẫn ảnh để React có thể hiển thị đúng
        $products->transform(function ($product) {
            if ($product->image) {
                $product->image = url('storage/' . $product->image);
            }
            return $product;
        });

        return response()->json($products);
    }

    // API lọc sản phẩm theo giá trị thuộc tính trên đường dẫn URL
    public function filterByAttributeValue($value)
    {
        $products = Product::whereHas('attributeValues', function ($query) use ($value) {
            $query->where('value', $value);
        })->get();

        // Thêm tiền tố đường dẫn ảnh để React có thể hiển thị đúng
        $products->transform(function ($product) {
            if ($product->image) {
                $product->image = url('storage/' . $product->image);
            }
            return $product;
        });

        return response()->json($products);
    }

    // API lọc sản phẩm theo tên thuộc tính trên đường dẫn URL
    public function filterByAttributeName($name)
    {
        // Tìm attribute_id theo tên thuộc tính
        $attribute = DB::table('attributes')->where('name', $name)->first();

        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found'], 404);
        }

        $products = Product::whereHas('attributeValues', function ($query) use ($attribute) {
            $query->where('attribute_id', $attribute->id);
        })->get();

        // Thêm tiền tố đường dẫn ảnh để React có thể hiển thị đúng
        $products->transform(function ($product) {
            if ($product->image) {
                $product->image = url('storage/' . $product->image);
            }
            return $product;
        });

        return response()->json($products);
    }

    // Lấy tất cả thuộc tính của sản phẩm theo product_id
    public function getAttributesByProductId($product_id)
    {
        $product = Product::with('attributeValues.attribute')->find($product_id);
        if (!$product) {
            return response()->json(['message' => 'Sản phẩm không tồn tại'], 404);
        }

        $attributes = $product->attributeValues->map(function ($attrVal) {
            return [
                'attribute_id' => $attrVal->attribute_id,
                'attribute_name' => $attrVal->attribute->name ?? null,
                'value' => $attrVal->value,
            ];
        });

        return response()->json([
            'product_id' => $product->id,
            'attributes' => $attributes,
        ]);
    }

    // Thực hiện chức năng "Sẵn hàng" và " Hết hàng trong Thành phố/ tỉnh
    public function getAvailableProductsByCity($city)
    {
        $products = DB::table('inventories')
            ->join('branches', 'inventories.warehouse_id', '=', 'branches.id')
            ->join('product_variants', 'inventories.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'product_variants.id as variant_id',
                'product_variants.name as variant_name',
                'product_variants.price',
                'product_variants.img',
                'branches.id as branch_id',
                'branches.name as branch_name',
                'branches.phone as branch_phone',
                'branches.email as branch_email',
                'branches.address as branch_address',
                'branches.city as branch_city',
                'branches.ward as branch_ward',
                DB::raw('SUM(inventories.quantity) as quantity')
            )
            ->where('branches.city', $city)
            ->where('branches.status', 1) // chỉ lấy chi nhánh đang hoạt động
            ->groupBy(
                'products.id',
                'products.name',
                'product_variants.id',
                'product_variants.name',
                'product_variants.price',
                'product_variants.img',
                'branches.id',
                'branches.name',
                'branches.phone',
                'branches.email',
                'branches.address',
                'branches.city',
                'branches.ward'
            )
            ->having('quantity', '>', 0)
            ->get();

        return response()->json($products);
    }
}
