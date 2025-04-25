<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Phpml\Clustering\KMeans;

class ProductController extends Controller
{
    // GET: List all products or a specific product
    public function index()
    {
        $products = Product::all();
        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Produk Tidak Ditemukan'], 404);
        }

        // Consume UserService for recommendation context
        $userId = request()->query('user_id', 1);
        $response = Http::get('http://localhost:8000/api/users/' . $userId);
        $user = $response->successful() ? $response->json()['user'] : null;

        return response()->json([
            'product' => $product,
            'recommended_for' => $user ? $user['name'] : 'Guest'
        ]);
    }

    // POST: Create a new product
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'category' => 'required|string|max:255'
        ]);

        $product = Product::create($request->only(['name', 'price', 'quantity', 'category']));
        return response()->json($product, 201);
    }

    // PUT: Update a product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Produk Tidak Ditemukan !'], 404);
        }

        $request->validate([
            'name' => 'string|max:255',
            'price' => 'numeric|min:0',
            'quantity' => 'integer|min:0',
            'category' => 'string|max:255'
        ]);

        $product->update($request->only(['name', 'price', 'quantity', 'category']));
        return response()->json($product);
    }

    // DELETE: Delete a product
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Produk Tidak Ditemukan !'], 404);
        }

        // Check if product is used in orders
        $response = Http::get('http://localhost:8002/api/orders/product/' . $id);
        if ($response->successful() && !empty($response->json())) {
            return response()->json(['error' => 'Cannot delete product with existing orders'], 400);
        }

        $product->delete();
        return response()->json(['message' => 'Produk Berhasil Dihapus !']);
    }
}
