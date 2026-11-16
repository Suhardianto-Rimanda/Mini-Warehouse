<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(new ProductCollection(Product::with('category')
            ->latest('id')
            ->paginate()
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'   => ['required', 'uuid', 'exists:categories,id'],
            'sku'           => ['required', 'string', 'max:50', 'unique:products,sku'],
            'name'          => ['required', 'string', 'max:255'],
            'unit'          => ['required', 'string', 'max:20'],
        ]);

        $product = DB::transaction(function () use ($data) {
            Category::lockForUpdate()->findOrFail($data['category_id']);

            return Product::create($data);
        }, 3);

        return response()->json(new ProductResource(
            $product->refresh()->load('category')
        ));
    }

    public function show(Product $product)
    {
        return response()->json(new ProductResource($product->load('category')));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id'   => ['required', 'uuid', 'exists:categories,id'],
            'sku'           => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product),],
            'name'          => ['required', 'string', 'max:255'],
            'unit'          => ['required', 'string', 'max:20'],
        ]);

        $product = DB::transaction(function () use ($product, $data) {
            Category::lockForUpdate()->findOrFail($data['category_id']);

            $product = Product::lockForUpdate()->findOrFail($product->id);
            $product->update($data);

            return $product;
        }, 3);

        return response()->json(new ProductResource($product->load('category')));
    }

    public function destroy(Product $product)
    {
        return DB::transaction(function () use ($product) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            abort_if(
                $product->stockMutations()->exists(),
                409,
                'Product has existing stock mutations.'
            );

            $product->delete();

            return response()->json([
                'message' => 'Product successfully deleted.',
            ]);
        }, 3);
    }
}
