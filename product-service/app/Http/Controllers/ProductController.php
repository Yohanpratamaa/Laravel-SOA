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

    // public function recommend($userId)
    // {
    //     // Ambil data pesanan dari OrderService
    //     $response = Http::get('http://localhost:8002/api/orders/user/' . $userId);
    //     if (!$response->successful()) {
    //         return response()->json(['error' => 'No orders found'], 404);
    //     }

    //     $orders = $response->json();
    //     $productIds = array_column($orders, 'product_id');

    //     // Ambil semua produk
    //     $products = Product::all()->toArray();
    //     $productData = array_map(fn($p) => [$p['price']], $products); // Contoh: Gunakan harga untuk clustering

    //     // Clustering sederhana dengan K-Means
    //     $kmeans = new KMeans(2);
    //     $clusters = $kmeans->cluster($productData);

    //     // Temukan produk dalam cluster yang sama dengan produk yang dibeli
    //     $recommended = [];
    //     foreach ($productIds as $pid) {
    //         $product = Product::find($pid);
    //         if ($product) {
    //             $index = array_search($product->price, array_column($productData, 0));
    //             $cluster = array_reduce($clusters, fn($carry, $c) => in_array($index, $c) ? $c : $carry, []);
    //             $recommended = array_merge($recommended, array_map(fn($i) => $products[$i], $cluster));
    //         }
    //     }

    //     return response()->json(array_unique($recommended, SORT_REGULAR));
    // }
}
