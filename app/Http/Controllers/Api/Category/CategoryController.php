<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(new CategoryCollection(Category::all()));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(new CategoryResource(Category::create($data)), 201);
    }

    public function show(Category $category)
    {
        return response()->json(new CategoryResource($category));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = DB::transaction(function () use ($category, $data) {
            $category = Category::lockForUpdate()->findOrFail($category->id);
            $category->update($data);

            return $category;
        }, 3);

        return response()->json(new CategoryResource($category));
    }

    public function destroy(Category $category)
    {
        return DB::transaction(function () use ($category) {
            $category = Category::lockForUpdate()->findOrFail($category->id);

            abort_if(
                $category->products()->exists(),
                409,
                'Kategori masih memiliki produk.'
            );

            $category->delete();

            return response()->json([
                'message' => 'Kategori berhasil dihapus.',
            ]);
        }, 3);
    }
}
