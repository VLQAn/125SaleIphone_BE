<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentSuccessMail;

class PaymentController extends Controller
{
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'order_id' => 'required',
            'email' => 'required|email',
        ]);

        $amount = $validated['amount'];
        $orderId = $validated['order_id'];
        $email = $validated['email'];

        // Mock: Create payment URL (pointing to our callback for simulation)
        // In real app, this would be VNPay/Momo URL
        $callbackUrl = url('/api/payment/callback?order_id=' . $orderId . '&status=success&email=' . $email);

        return response()->json([
            'message' => 'Order created successfully',
            'payment_url' => $callbackUrl
        ]);
    }

    public function callback(Request $request)
    {
        $status = $request->input('status');
        $email = $request->input('email');

        if ($status == 'success') {
            // Logic to update order status in Database would be here...

            // Send confirmation email
            if ($email) {
                try {
                    Mail::to($email)->send(new PaymentSuccessMail());
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Payment successful but email failed to send',
                        'error' => $e->getMessage()
                    ], 200); // Still 200 because payment succeeded
                }
            }

            return response()->json(['message' => 'Payment successful. Confirmation email sent.']);
        }

        return response()->json(['message' => 'Payment failed or cancelled.'], 400);
    }

    // Kept for backward compatibility or testing
    public function sendMail(Request $request)
    {
        return $this->callback($request->merge(['status' => 'success']));
    }
}
