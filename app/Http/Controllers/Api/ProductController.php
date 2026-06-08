<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->products()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'price_discount' => 'nullable|numeric|min:0',
            'url' => 'nullable|url',
            'images' => 'nullable|array',
            'attributes' => 'nullable|array',
        ]);

        $product = $request->user()->products()->create($data);

        return response()->json($product, 201);
    }

    public function show(Request $request, Product $product)
    {
        abort_if($product->user_id !== $request->user()->id, 403);

        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        abort_if($product->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'price_discount' => 'nullable|numeric|min:0',
            'url' => 'nullable|url',
            'images' => 'nullable|array',
            'attributes' => 'nullable|array',
            'active' => 'boolean',
        ]);

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(Request $request, Product $product)
    {
        abort_if($product->user_id !== $request->user()->id, 403);

        $product->delete();

        return response()->json(null, 204);
    }

    public function uploadImage(Request $request, Product $product)
    {
        abort_if($product->user_id !== $request->user()->id, 403);

        $request->validate(['image' => 'required|image|max:5120']);

        $path = $request->file('image')->store("products/{$product->id}", 'public');
        $images = $product->images ?? [];
        $images[] = $path;
        $product->update(['images' => $images]);

        return response()->json(['path' => $path, 'url' => asset("storage/{$path}")]);
    }
}
