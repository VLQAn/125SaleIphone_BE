<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DonHangController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $status = $request->query('status', 'Tất cả');

        $query = Order::where('IdUser', $userId);

        if ($status !== 'Tất cả') {
            $statusMap = [
                'Đang xử lý' => 0,
                'Đang giao hàng' => 1,
                'Đã giao hàng' => 2,
                'Hoàn thành' => 3,
                'Đã huỷ' => 4
            ];
            
            if (isset($statusMap[$status])) {
                $query->where('Status', $statusMap[$status]);
            }
        }

        $orders = $query->with(['items.product.variants'])
                        ->orderByDesc('IdOrder')
                        ->get();

        $user = Auth::user();

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'user' => $user,
            'selectedStatus' => $status
        ]);
    }

    public function huyDonHang($id)
    {
        $order = Order::where('IdOrder', $id)->first();

        if ($order) {
            $order->update(['Status' => 4]);
            return response()->json([
                'success' => true,
                'message' => 'Bạn đã huỷ đơn hàng'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng'], 404);
    }

    public function chiTiet($id)
    {
        $order = Order::where('IdOrder', $id)
            ->with(['items.product.variants', 'address'])
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng'], 404);
        }

        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'user' => $user
            ]
        ]);
    }
}