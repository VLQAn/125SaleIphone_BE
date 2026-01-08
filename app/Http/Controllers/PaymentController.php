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

    public function checkout(Request $request)
    {
        // 1. Lưu các thông tin đơn hàng vào DB trước
        $order = Order::create([
            'user_id' => $request->user_id,
            'total_amount' => $request->total_amount,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            // ... các trường khác
        ]);

        // 2. Nếu là MoMo, tạo payUrl
        if ($request->payment_method == 'momo') {
            return $this->createMomoPayment($order);
        }

        // Nếu là COD hoặc khác
        return response()->json([
            'status' => 'success',
            'order_id' => $order->id,
            'message' => 'Đặt hàng thành công!'
        ]);
    }

    private function createMomoPayment($order)
    {
        $endpoint = env('MOMO_API_ENDPOINT');
        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');

        $orderInfo = "Thanh toán đơn hàng #" . $order->id;
        $amount = $order->total_amount;
        $orderId = $order->id . "_" . time();
        $requestId = $orderId;
        $extraData = "";

        // Tạo chữ ký (Signature)
        $rawHash = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=" . env('MOMO_NOTIFY_URL') . "&orderId=$orderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=" . env('MOMO_RETURN_URL') . "&requestId=$requestId&requestType=captureWallet";
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = [
            'partnerCode' => $partnerCode,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => env('MOMO_RETURN_URL'),
            'ipnUrl' => env('MOMO_NOTIFY_URL'),
            'extraData' => $extraData,
            'requestType' => 'captureWallet',
            'signature' => $signature,
            'lang' => 'vi'
        ];

        $response = Http::post($endpoint, $data);
        $result = $response->json();

        if ($result && isset($result['payUrl'])) {
            return response()->json([
                'payment_url' => $result['payUrl'], // Trả link này về Frontend
                'order_id' => $order->id
            ]);
        }

        return response()->json(['message' => 'Lỗi khởi tạo MoMo'], 500);
    }
}
