<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function history($uniqueUserId)
    {
        $user = User::findByUniqueUserId($uniqueUserId);

        if (!in_array(Auth::user()->role->name, ['super admin', 'moderator', 'customer'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (Auth::user()->role->name == "customer") {
            if (Auth::id() != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // Assuming the relationship is defined as: User hasMany Orders
        $paidOrders = $user->orders()->where('status', 'paid')->get();

        return response()->json([
            'user' => $user,
            'orders' => $paidOrders
        ]);
    }
}
