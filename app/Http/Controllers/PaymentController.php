<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentSuccessMail;
use App\Models\Order;
use App\Models\CartItem;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Callback xử lý kết quả thanh toán từ payment gateway (Giả lập)
     */
    public function callback(Request $request)
    {
        $status = $request->input('status');
        $orderId = $request->input('order_id');
        $email = $request->input('email');

        if ($status == 'success' && $orderId) {
            // Tìm đơn hàng theo IdOrder (Primary Key là string)
            $order = Order::where('IdOrder', $orderId)->first();

            if ($order) {
                // Cập nhật trạng thái đơn hàng thành 0 (Đang xử lý) sau khi thanh toán thành công
                // Giả sử Status 0 là trạng thái mặc định cho đơn hàng đã thanh toán/xác nhận
                $order->update(['Status' => 0]);

                // Xóa giỏ hàng của user sau khi thanh toán thành công
                CartItem::where('IdCart', $order->IdUser)->delete();

                // Gửi email xác nhận
                if ($email) {
                    try {
                        // Lưu ý: Mailable PaymentSuccessMail có thể cần tham số, 
                        // nhưng ở đây tôi dùng theo cấu trúc hiện tại của dự án
                        Mail::to($email)->send(new PaymentSuccessMail());
                    } catch (\Exception $e) {
                        Log::error('Gửi mail thất bại trong callback: ' . $e->getMessage());
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán thành công. Giỏ hàng đã được làm mới.',
                    'order_id' => $orderId
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Thanh toán thất bại hoặc đơn hàng không tồn tại.'
        ], 400);
    }

    /**
     * Endpoint hỗ trợ gửi mail thủ công hoặc test
     */
    public function sendMail(Request $request)
    {
        $email = $request->input('email');
        if ($email) {
            try {
                Mail::to($email)->send(new PaymentSuccessMail());
                return response()->json(['message' => 'Đã gửi email test thành công']);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Lỗi: ' . $e->getMessage()], 500);
            }
        }
        return response()->json(['message' => 'Thiếu email'], 400);
    }
}