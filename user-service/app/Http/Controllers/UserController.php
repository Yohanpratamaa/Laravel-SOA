<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    // GET: List all users or a specific user
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Consume OrderService to get user orders
        $response = Http::get('http://localhost:8002/api/orders/user/' . $id);
        $orders = $response->successful() ? $response->json() : [];

        return response()->json([
            'user' => $user,
            'orders' => $orders
        ]);
    }

    // POST: Create a new user
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email'
        ]);

        $user = User::create($request->only(['name', 'email']));
        return response()->json($user, 201);
    }

    // PUT: Update a user
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $id
        ]);

        $user->update($request->only(['name', 'email']));
        return response()->json($user);
    }

    // DELETE: Delete a user
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if user has orders
        $response = Http::get('http://localhost:8002/api/orders/user/' . $id);
        if ($response->successful() && !empty($response->json())) {
            return response()->json(['error' => 'Cannot delete user with existing orders'], 400);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
