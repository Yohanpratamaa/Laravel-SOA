<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    // GET: List all orders or orders by user/product
    public function index()
    {
        $orders = Order::all();
        return response()->json($orders);
    }

    public function getUserOrders($userId)
    {
        $orders = Order::where('user_id', $userId)->get();
        return response()->json($orders);
    }

    public function getProductOrders($productId)
    {
        $orders = Order::where('product_id', $productId)->get();
        return response()->json($orders);
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
