<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddress;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentSuccessMail;

class OrderController extends Controller
{
    /**
     * Checkout - Tạo đơn hàng từ giỏ hàng
     */
    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'payment_method' => 'required|string|in:COD,BANK,MOMO',
            'email' => 'nullable|email'
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $userId = Auth::id();

        // Lấy các sản phẩm trong giỏ hàng
        $cartItems = CartItem::where('IdCart', $userId)
            ->with('product.variants')
            ->get();

        // Kiểm tra giỏ hàng có sản phẩm không
        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Giỏ hàng trống. Vui lòng thêm sản phẩm trước khi thanh toán.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Tính tổng giá trị đơn hàng
            $totalPrice = 0;
            foreach ($cartItems as $item) {
                // Lấy giá từ variant đầu tiên hoặc từ product
                $variant = ProductVariant::find($item->IdProductVar);
                $price = $variant ? $variant->Price : 0;
                $totalPrice += $price * $item->Quantity;
            }

            // Tạo Order
            $orderId = 'ORD' . Str::upper(Str::random(10));
            $order = Order::create([
                'IdOrder' => $orderId,
                'IdUser' => $userId,
                'TotalPrice' => $totalPrice,
                'Status' => 0 // Đang xử lý
            ]);

            // Tạo OrderAddress
            $orderAddress = OrderAddress::create([
                'IdOrderAdd' => 'ADDR' . Str::upper(Str::random(10)),
                'IdOrder' => $orderId,
                'FullName' => $validated['fullname'],
                'Phone' => $validated['phone'],
                'Address' => $validated['address']
            ]);

            // Tạo OrderItems
            foreach ($cartItems as $item) {
                $variant = $item->product->variants->first();
                $unitPrice = $variant ? $variant->Price : 0;

                OrderItem::create([
                    'IdOrderItem' => 'ITEM' . Str::upper(Str::random(10)),
                    'IdOrder' => $orderId,
                    'IdProduct' => $item->IdProduct,
                    'Quantity' => $item->Quantity,
                    'UnitPrice' => $unitPrice
                ]);
            }

            $paymentMethod = $validated['payment_method'];
            $paymentUrl = null;

            if ($paymentMethod === 'COD') {
                // Xóa giỏ hàng ngay cho COD
                CartItem::where('IdCart', $userId)->delete();

                // Gửi email xác nhận
                if (!empty($validated['email'])) {
                    try {
                        Mail::to($validated['email'])->send(new PaymentSuccessMail());
                    } catch (\Exception $e) {
                        \Log::error('Email sending failed: ' . $e->getMessage());
                    }
                }
            } else {
                // Đối với BANK hoặc MOMO, tạo URL thanh toán giả lập
                // Link này sẽ dẫn người dùng đến trang callback với trạng thái thành công
                $paymentUrl = url("/api/payment/callback?order_id={$orderId}&status=success&email=" . ($validated['email'] ?? ''));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $paymentMethod === 'COD' ? 'Đặt hàng thành công!' : 'Đang chuyển hướng đến trang thanh toán...',
                'data' => [
                    'order_id' => $orderId,
                    'total_price' => $totalPrice,
                    'status' => Order::STATUS_MAP[0],
                    'payment_method' => $paymentMethod,
                    'payment_url' => $paymentUrl,
                    'shipping_info' => [
                        'fullname' => $validated['fullname'],
                        'phone' => $validated['phone'],
                        'address' => $validated['address']
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách đơn hàng của user
     */
    public function index()
    {
        $userId = Auth::id();

        $orders = Order::where('IdUser', $userId)
            ->with(['items.product.variants'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->IdOrder,
                    'total_price' => $order->TotalPrice,
                    'status' => Order::STATUS_MAP[$order->Status] ?? 'Unknown',
                    'status_code' => $order->Status,
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                    'items_count' => $order->items->count()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Lấy chi tiết đơn hàng
     */
    public function chiTiet($id)
    {
        $userId = Auth::id();

        $order = Order::where('IdOrder', $id)
            ->where('IdUser', $userId)
            ->with(['items.product.variants'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }

        $orderAddress = OrderAddress::where('IdOrder', $id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->IdOrder,
                'total_price' => $order->TotalPrice,
                'status' => Order::STATUS_MAP[$order->Status] ?? 'Unknown',
                'status_code' => $order->Status,
                'created_at' => $order->created_at->format('d/m/Y H:i'),
                'shipping_info' => $orderAddress ? [
                    'fullname' => $orderAddress->FullName,
                    'phone' => $orderAddress->Phone,
                    'address' => $orderAddress->Address
                ] : null,
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_id' => $item->IdProduct,
                        'product_name' => $item->product->NameProduct ?? 'N/A',
                        'quantity' => $item->Quantity,
                        'unit_price' => $item->UnitPrice,
                        'subtotal' => $item->Quantity * $item->UnitPrice
                    ];
                })
            ]
        ]);
    }

    /**
     * Hủy đơn hàng
     */
    public function huyDonHang($id)
    {
        $userId = Auth::id();

        $order = Order::where('IdOrder', $id)
            ->where('IdUser', $userId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }

        // Chỉ cho phép hủy đơn hàng đang xử lý hoặc đang giao hàng
        if ($order->Status >= 2) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng đã giao hoặc đã hoàn thành'
            ], 400);
        }

        $order->update(['Status' => 4]); // Đã hủy

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy đơn hàng thành công'
        ]);
    }
}
