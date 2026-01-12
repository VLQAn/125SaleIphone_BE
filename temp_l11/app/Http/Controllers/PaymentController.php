<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Mail\PaymentSuccessMail;
use App\Models\Order;
use App\Models\CartItem;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Callback xử lý kết quả thanh toán từ MoMo
     */
    public function callback(Request $request)
    {
        Log::info('MoMo Callback received:', $request->all());
        
        $resultCode = $request->input('resultCode');
        $orderId = $request->input('orderId');

        // resultCode = 0 là thành công
        if ($resultCode == 0 && $orderId) {
            // Tách order ID (format: IdOrder_timestamp)
            $parts = explode('_', $orderId);
            $actualOrderId = $parts[0];
            
            $order = Order::where('IdOrder', $actualOrderId)->first();

            if ($order) {
                // Cập nhật trạng thái: 1 = Đã thanh toán
                $order->update(['Status' => 1]);

                // Xóa giỏ hàng
                if ($order->user) {
                    CartItem::whereHas('cart', function ($query) use ($order) {
                        $query->where('IdUser', $order->IdUser);
                    })->delete();
                }

                // Gửi email
                if ($order->user && $order->user->email) {
                    try {
                        Mail::to($order->user->email)->send(new PaymentSuccessMail());
                    } catch (\Exception $e) {
                        Log::error('Gửi mail thất bại: ' . $e->getMessage());
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán thành công',
                    'order_id' => $actualOrderId
                ]);
            }
        }

        Log::warning('MoMo callback failed:', ['resultCode' => $resultCode, 'orderId' => $orderId]);
        
        return response()->json([
            'success' => false,
            'message' => 'Thanh toán thất bại'
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
        $notifyUrl = env('MOMO_NOTIFY_URL');
        $returnUrl = env('MOMO_RETURN_URL');

        $orderInfo = "Thanh toan don hang " . $order->IdOrder;
        $amount = (int)$order->total_amount;
        $orderId = $order->IdOrder . "_" . time();
        $requestId = time() . "";
        $extraData = base64_encode($order->IdOrder);
        $requestType = 'captureWallet';

        // Tạo signature theo format MoMo
        $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $notifyUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $returnUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
        
        Log::info('MoMo Raw Hash:', ['hash' => $rawHash]);
        
        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'Sale iPhone 125',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $returnUrl,
            'ipnUrl' => $notifyUrl,
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature,
            'lang' => 'vi',
            'autoCapture' => true
        ];

        Log::info('MoMo Request Data:', $data);

        try {
            $response = Http::timeout(30)->post($endpoint, $data);
            $result = $response->json();
            
            Log::info('MoMo Response:', $result);

            if ($result && isset($result['payUrl'])) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $result['payUrl'],
                    'order_id' => $order->IdOrder
                ]);
            }
        } catch (\Exception $e) {
            Log::error('MoMo Payment Error:', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Lỗi khởi tạo MoMo'
        ], 500);
    }
}
