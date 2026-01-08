<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class PaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;
    public $orderId;
    public $totalAmount;
    public $items;
    /**
     * Create a new message instance.
     */
    public function __construct($orderId, $totalAmount, $items)
    {
        $this->orderId = $orderId;
        $this->totalAmount = $totalAmount;
        $this->items = $items;
    }
    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Xác nhận đơn hàng #' . $this->orderId)
                    ->view('emails.payment-success')
                    ->with([
                        'orderId' => $this->orderId,
                        'totalAmount' => number_format($this->totalAmount, 0, ',', '.') . ' VNĐ',
                        'items' => $this->items,
                    ]);
    }
}
