<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(CheckPermission::class.':products,view',   only: ['index', 'show']),
            new Middleware(CheckPermission::class.':products,create', only: ['store']),
            new Middleware(CheckPermission::class.':products,edit',   only: ['update', 'uploadImage']),
            new Middleware(CheckPermission::class.':products,delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $products = Product::where('user_id', $request->user()->accountId())
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

        $product = Product::create(['user_id' => $request->user()->accountId()] + $data);

        return response()->json($product, 201);
    }

    public function show(Request $request, Product $product)
    {
        abort_if($product->user_id !== $request->user()->accountId(), 403);

        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        abort_if($product->user_id !== $request->user()->accountId(), 403);

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
        abort_if($product->user_id !== $request->user()->accountId(), 403);

        $product->delete();

        return response()->json(null, 204);
    }

    public function uploadImage(Request $request, Product $product)
    {
        abort_if($product->user_id !== $request->user()->accountId(), 403);

        $request->validate(['image' => 'required|image|max:5120']);

        $path = $request->file('image')->store("products/{$product->id}", 'r2');
        $rawImages = json_decode($product->getRawOriginal('images'), true) ?? [];
        $rawImages[] = $path;
        $product->update(['images' => $rawImages]);

        return response()->json(['path' => $path, 'url' => Storage::disk('r2')->url($path)]);
    }
}
