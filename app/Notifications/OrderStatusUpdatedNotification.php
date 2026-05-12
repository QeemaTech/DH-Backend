<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\VendorOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $status,
        public ?VendorOrder $vendorOrder = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your order status has been updated'))
            ->view('emails.orders.status-updated', [
                'order' => $this->order,
                'vendorOrder' => $this->vendorOrder,
                'status' => $this->status,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'vendor_order_id' => $this->vendorOrder?->id,
            'status' => $this->status,
            'title' => __('Your order status has been updated'),
            'message' => $this->buildMessage(),
        ];
    }

    protected function buildMessage(): string
    {
        if ($this->vendorOrder) {
            return __(
                'Vendor order #:vendor_order_id of order #:order_id status changed to :status',
                [
                    'vendor_order_id' => $this->vendorOrder->id,
                    'order_id' => $this->order->id,
                    'status' => $this->status,
                ]
            );
        }

        return __(
            'Order #:order_id status changed to :status',
            [
                'order_id' => $this->order->id,
                'status' => $this->status,
            ]
        );
    }
}
