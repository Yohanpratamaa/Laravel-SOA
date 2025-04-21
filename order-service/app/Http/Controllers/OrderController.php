<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // GET: List all orders or orders by user/product
    public function index()
    {
        try {
            // Ambil semua order
            $orders = Order::all();

            // Kumpulkan user_id dan product_id unik
            $userIds = $orders->pluck('user_id')->unique()->toArray();
            $productIds = $orders->pluck('product_id')->unique()->toArray();

            // Cache untuk users dan products
            $users = Cache::remember('users_' . md5(implode(',', $userIds)), 60, function () use ($userIds) {
                $usersData = [];
                foreach ($userIds as $userId) {
                    $response = Http::timeout(5)->get('http://localhost:8000/api/users/' . $userId);
                    if ($response->successful()) {
                        $usersData[$userId] = $response->json()['user'] ?? null;
                    }
                }
                return $usersData;
            });

            $products = Cache::remember('products_' . md5(implode(',', $productIds)), 60, function () use ($productIds) {
                $productsData = [];
                foreach ($productIds as $productId) {
                    $response = Http::timeout(5)->get('http://localhost:8001/api/products/' . $productId);
                    if ($response->successful()) {
                        $productsData[$productId] = $response->json();
                    }
                }
                return $productsData;
            });

            // Format respons dengan detail user dan produk
            $formattedOrders = $orders->map(function ($order) use ($users, $products) {
                return [
                    'id' => $order->id,
                    'user' => $users[$order->user_id] ?? null,
                    'product' => $products[$order->product_id] ?? null,
                    'quantity' => $order->quantity,
                    'total' => $order->total,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

            return response()->json($formattedOrders);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('Gagal terhubung ke UserService/ProductService: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal terhubung ke service'], 503);
        } catch (\Exception $e) {
            \Log::error('Error saat mengambil orders: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
    public function getUserOrders($userId)
    {
        try {
            // Ambil order berdasarkan user_id
            $orders = Order::where('user_id', $userId)->get();

            // Ambil detail user dari UserService
            $user = Cache::remember('user_' . $userId, 60, function () use ($userId) {
                $response = Http::timeout(5)->get('http://localhost:8000/api/users/' . $userId);
                return $response->successful() ? ($response->json()['user'] ?? null) : null;
            });

            // Jika user tidak ditemukan, kembalikan error
            if (!$user) {
                \Log::warning('User tidak ditemukan untuk user_id: ' . $userId);
                return response()->json(['error' => 'User Tidak Ditemukan !'], 404);
            }

            // Format respons dengan detail user dan orders
            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'quantity' => $order->quantity,
                    'total' => $order->total,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

            return response()->json([
                'user' => $user,
                'orders' => $formattedOrders
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('Gagal terhubung ke UserService: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal terhubung ke UserService'], 503);
        } catch (\Exception $e) {
            \Log::error('Error saat mengambil orders untuk user_id: ' . $userId . ': ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function getProductOrders($productId)
    {
        try {
            // Ambil order berdasarkan product_id
            $orders = Order::where('product_id', $productId)->get();

            // Ambil detail produk dari ProductService
            $product = Cache::remember('product_' . $productId, 60, function () use ($productId) {
                $response = Http::timeout(5)->get('http://localhost:8001/api/products/' . $productId);
                return $response->successful() ? $response->json() : null;
            });

            // Jika produk tidak ditemukan, kembalikan error
            if (!$product) {
                \Log::warning('Produk tidak ditemukan untuk product_id: ' . $productId);
                return response()->json(['error' => 'Produk Tidak Ditemukan !'], 404);
            }

            // Format respons dengan detail produk dan orders
            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'quantity' => $order->quantity,
                    'total' => $order->total,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

            return response()->json([
                'product' => $product,
                'orders' => $formattedOrders
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('Gagal terhubung ke ProductService: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal terhubung ke ProductService'], 503);
        } catch (\Exception $e) {
            \Log::error('Error saat mengambil orders untuk product_id: ' . $productId . ': ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    // POST: Create a new order
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'total' => 'required|numeric|min:0',
        ]);

        // Validate user
        $userResponse = Http::get('http://localhost:8000/api/users/' . $request->user_id);
        if (!$userResponse->successful()) {
            return response()->json(['error' => 'User Tidak Ditemukan !'], 404);
        }

        // Validate product
        $productResponse = Http::get('http://localhost:8001/api/products/' . $request->product_id);
        if (!$productResponse->successful()) {
            return response()->json(['error' => 'Produk Tidak Ditemukan !'], 404);
        }

        $order = Order::create($request->all());
        return response()->json($order, 201);
    }

    // PUT: Update an order
    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['error' => 'Order Tidak Ditemukan !'], 404);
        }

        $request->validate([
            'user_id' => 'integer',
            'product_id' => 'integer',
            'quantity' => 'integer|min:1',
            'total' => 'numeric|min:0',
        ]);

        // Validate user if provided
        if ($request->has('user_id')) {
            $userResponse = Http::get('http://localhost:8000/api/users/' . $request->user_id);
            if (!$userResponse->successful()) {
                return response()->json(['error' => 'User Tidak Ditemukan !'], 404);
            }
        }

        // Validate product if provided
        if ($request->has('product_id')) {
            $productResponse = Http::get('http://localhost:8001/api/products/' . $request->product_id);
            if (!$productResponse->successful()) {
                return response()->json(['error' => 'Produk Tidak Ditemukan !'], 404);
            }
        }

        $order->update($request->only(['user_id', 'product_id', 'quantity', 'total']));
        return response()->json($order);
    }

    // DELETE: Delete an order
    public function destroy($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['error' => 'Order Tidak Ditemukan !'], 404);
        }

        $order->delete();
        return response()->json(['message' => 'Order berhasil dihapus']);
    }
}
