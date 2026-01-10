<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddress;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function checkout(Request $request) {
    $validated = $request->validate([
        'fullname' => 'required|string',
        'phone' => 'required|string',
        'address' => 'required|string',
        'payment_method' => 'required|string',
        'items' => 'required|array',
        'items.*.IdProduct' => 'required|integer',
        'items.*.Quantity' => 'required|integer',
        'items.*.Price' => 'required|numeric',
    ]);

    // Xử lý đơn hàng



        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Tạo Order ID
            $lastOrder = Order::orderBy('IdOrder', 'desc')->first();
            $number = $lastOrder ? intval(substr($lastOrder->IdOrder, 1)) + 1 : 1;
            $orderId = 'O' . str_pad($number, 4, '0', STR_PAD_LEFT);

            // Tính tổng tiền
            $totalPrice = collect($validated['items'])->sum(function ($item) {
                return $item['Price'] * $item['Quantity'];
            });

            // Tạo đơn hàng
            $order = Order::create([
                'IdOrder' => $orderId,
                'IdUser' => $user->IdUser,
                'TotalPrice' => $totalPrice,
                'Status' => 0, // 0 = Đang xử lý
            ]);

            // Tạo order address
            $lastAddress = OrderAddress::orderBy('IdOrderAdd', 'desc')->first();
            $addressNumber = $lastAddress ? intval(substr($lastAddress->IdOrderAdd, 2)) + 1 : 1;
            $addressId = 'OA' . str_pad($addressNumber, 4, '0', STR_PAD_LEFT);

            OrderAddress::create([
                'IdOrderAdd' => $addressId,
                'IdOrder' => $orderId,
                'FullName' => $validated['fullname'],
                'Phone' => $validated['phone'],
                'Address' => $validated['address'],
            ]);

            // Tạo order items
            foreach ($validated['items'] as $item) {
                $lastItem = OrderItem::orderBy('IdOrderItem', 'desc')->first();
                $itemNumber = $lastItem ? intval(substr($lastItem->IdOrderItem, 2)) + 1 : 1;
                $itemId = 'OI' . str_pad($itemNumber, 4, '0', STR_PAD_LEFT);

                OrderItem::create([
                    'IdOrderItem' => $itemId,
                    'IdOrder' => $orderId,
                    'IdProduct' => $item['IdProduct'],
                    'Quantity' => $item['Quantity'],
                    'UnitPrice' => $item['Price'],
                ]);
            }

            // Xóa giỏ hàng
            $cart = $user->cart;
            if ($cart) {
                $cart->items()->delete();
            }

            DB::commit();

            // Xử lý payment method
            $responseData = [
                'order_id' => $orderId,
                'total_amount' => $totalPrice,
                'status' => Order::STATUS_MAP[0],
            ];

            if ($validated['payment_method'] === 'BANK') {
                // TODO: Tích hợp với cổng thanh toán ngân hàng
                $responseData['payment_url'] = 'https://bank-payment-gateway.com/...';
            } elseif ($validated['payment_method'] === 'MOMO') {
                // TODO: Tích hợp với MoMo API
                $responseData['payment_url'] = 'https://momo.vn/payment/...';
            }

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công',
                'data' => $responseData,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo đơn hàng: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Lấy danh sách đơn hàng của user
    public function index()
    {
        $user = Auth::user();
        $orders = Order::where('IdUser', $user->IdUser)
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Map status sang text
        $orders->transform(function ($order) {
            $order->status_text = Order::STATUS_MAP[$order->Status] ?? 'Không xác định';
            return $order;
        });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    // Lấy chi tiết 1 đơn hàng
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::where('IdOrder', $id)
            ->where('IdUser', $user->IdUser)
            ->with(['items.product'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng',
            ], 404);
        }

        // Lấy địa chỉ giao hàng
        $address = OrderAddress::where('IdOrder', $id)->first();
        $order->address = $address;
        $order->status_text = Order::STATUS_MAP[$order->Status] ?? 'Không xác định';

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    // Hủy đơn hàng
    public function cancel($id)
    {
        $user = Auth::user();
        $order = Order::where('IdOrder', $id)
            ->where('IdUser', $user->IdUser)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng',
            ], 404);
        }

        // Chỉ cho phép hủy đơn đang xử lý hoặc đang giao
        if ($order->Status > 2) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng này',
            ], 400);
        }

        $order->update(['Status' => 4]); // 4 = Đã huỷ

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy đơn hàng',
        ]);
    }
}
