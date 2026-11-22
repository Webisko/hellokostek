<?php

namespace App\Domain\Communication;

use App\Mail\DigitalDeliveryMail;
use App\Mail\OrderPlacedAdminMail;
use App\Mail\OrderPlacedCustomerMail;
use App\Mail\ServiceFollowupMail;
use App\Models\Order;
use App\Models\TransactionalEmailLog;
use App\Support\StoreSettings;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TransactionalEmailService
{
    public function __construct(
        private readonly StoreSettings $storeSettings,
		private readonly MailDeliveryTargetResolver $mailDeliveryTargetResolver,
    ) {
    }

    public function sendOrderPlacedEmails(Order $order): void
    {
        $order->loadMissing(['items', 'fulfillmentActions']);

        $this->deliver(
            emailType: 'order_placed_customer',
            recipient: $order->customer_email,
            order: $order,
            mailable: new OrderPlacedCustomerMail($order),
        );

        if (filled($this->storeSettings->adminNotificationEmail())) {
            $this->deliver(
                emailType: 'order_placed_admin',
                recipient: (string) $this->storeSettings->adminNotificationEmail(),
                order: $order,
                mailable: new OrderPlacedAdminMail($order),
            );
        }

        if ($order->items->contains(fn ($item): bool => $item->product_type->value === 'digital')) {
            $this->deliver(
                emailType: 'digital_delivery',
                recipient: $order->customer_email,
                order: $order,
                mailable: new DigitalDeliveryMail($order),
            );
        }

        if ($order->items->contains(fn ($item): bool => $item->product_type->value === 'service')) {
            $this->deliver(
                emailType: 'service_followup',
                recipient: $order->customer_email,
                order: $order,
                mailable: new ServiceFollowupMail($order),
            );
        }
    }

    public function sendAbandonedCartEmail(Order $order): void
    {
        $order->loadMissing(['items']);

        $this->deliver(
            emailType: 'abandoned_cart',
            recipient: $order->customer_email,
            order: $order,
            mailable: new \App\Mail\AbandonedCartMail($order),
        );
    }

    public function sendOrderPaidEmail(Order $order): void
    {
        $order->loadMissing(['items', 'invoices']);

        $this->deliver(
            emailType: 'order_paid_customer',
            recipient: $order->customer_email,
            order: $order,
            mailable: new \App\Mail\OrderPaidCustomerMail($order),
            attachmentPath: null,
        );
    }

    public function sendShippingConfirmationEmail(Order $order): void
    {
        $pdfPath = storage_path('app/invoices/' . $order->number . '.pdf');
        $attachment = ($order->isCod() && file_exists($pdfPath)) ? $pdfPath : null;

        $this->deliver(
            emailType: 'order_shipped_customer',
            recipient: $order->customer_email,
            order: $order,
            mailable: new \App\Mail\ShippingConfirmationMail($order),
            attachmentPath: $attachment,
        );
    }

    public function sendOrderReturnConfirmationEmail(\App\Models\OrderReturn $orderReturn): void
    {
        $order = $orderReturn->order;
        $order->loadMissing(['items']);
        $orderReturn->loadMissing(['items.orderItem']);

        $this->deliver(
            emailType: 'order_return_confirmation',
            recipient: $order->customer_email,
            order: $order,
            mailable: new \App\Mail\OrderReturnConfirmationMail($orderReturn),
            attachmentPath: null,
            orderReturn: $orderReturn
        );
    }

    private function deliver(string $emailType, string $recipient, Order $order, Mailable $mailable, ?string $attachmentPath = null, ?\App\Models\OrderReturn $orderReturn = null): void
    {
		$deliveryTarget = $this->mailDeliveryTargetResolver->resolve($recipient);
		$deliveryMetadata = $deliveryTarget['metadata'];

        $dynamicMailable = $this->resolveDynamicMail($emailType, $order, $orderReturn);
        $activeMailable = $dynamicMailable ?? $mailable;

        if ($attachmentPath && file_exists($attachmentPath)) {
            $activeMailable->attach($attachmentPath);
        }

        $log = TransactionalEmailLog::query()->create([
            'order_id' => $order->id,
            'email_type' => $emailType,
			'recipient' => $deliveryTarget['recipient'],
            'subject' => (string) $activeMailable->envelope()->subject,
            'status' => 'pending',
            'payload' => [
                'order_number' => $order->number,
                'customer_email' => $order->customer_email,
            ],
			'metadata' => $deliveryMetadata,
        ]);

        try {
			Mail::to($deliveryTarget['recipient'])->send($activeMailable);

            $log->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
				'metadata' => array_merge($deliveryMetadata, [
                    'mailer' => config('mail.default'),
				]),
            ])->save();
        } catch (Throwable $exception) {
            $log->forceFill([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
				'metadata' => array_merge($deliveryMetadata, [
                    'mailer' => config('mail.default'),
                    'exception' => get_class($exception),
				]),
            ])->save();
        }
    }

    private function resolveDynamicMail(string $emailType, Order $order, ?\App\Models\OrderReturn $orderReturn = null): ?Mailable
    {
        $template = \App\Models\EmailTemplate::query()->where('key', $emailType)->first();
        if ($template === null) {
            return null;
        }

        $itemsHtml = '';
        $itemsPlain = '';
        foreach ($order->items as $item) {
            $escapedName = e($item->name);
            $itemsHtml .= "<li>{$escapedName} x {$item->quantity}</li>";
            $itemsPlain .= "{$item->name} x {$item->quantity}; ";
        }
        if ($itemsHtml !== '') {
            $itemsHtml = "<ul>{$itemsHtml}</ul>";
        }

        $subjectPlaceholders = [
            '{order_number}' => $order->number,
            '{customer_first_name}' => $order->customer_first_name,
            '{customer_last_name}' => $order->customer_last_name,
            '{customer_email}' => $order->customer_email,
            '{total_amount}' => number_format($order->total_amount / 100, 2, ',', ' ') . ' ' . $order->currency,
            '{items_list}' => trim($itemsPlain),
        ];

        $htmlPlaceholders = [
            '{order_number}' => e($order->number),
            '{customer_first_name}' => e($order->customer_first_name),
            '{customer_last_name}' => e($order->customer_last_name),
            '{customer_email}' => e($order->customer_email),
            '{total_amount}' => e(number_format($order->total_amount / 100, 2, ',', ' ') . ' ' . $order->currency),
            '{items_list}' => $itemsHtml,
        ];

        if ($orderReturn) {
            $returnedItemsHtml = '';
            $returnedItemsPlain = '';
            foreach ($orderReturn->items as $item) {
                $productName = $item->orderItem?->name ?? 'Produkt';
                $returnedItemsHtml .= '<li>' . e($productName) . " x {$item->quantity}</li>";
                $returnedItemsPlain .= "{$productName} x {$item->quantity}; ";
            }
            if ($returnedItemsHtml !== '') {
                $returnedItemsHtml = "<ul>{$returnedItemsHtml}</ul>";
            }

            $returnDate = $orderReturn->created_at ? $orderReturn->created_at->toIso8601String() : now()->toIso8601String();
            $subjectPlaceholders['{return_number}'] = $orderReturn->return_number;
            $subjectPlaceholders['{returned_items_list}'] = trim($returnedItemsPlain);
            $subjectPlaceholders['{return_date}'] = $returnDate;

            $htmlPlaceholders['{return_number}'] = e($orderReturn->return_number);
            $htmlPlaceholders['{returned_items_list}'] = $returnedItemsHtml;
            $htmlPlaceholders['{return_date}'] = e($returnDate);
        }

        $subject = strtr($template->subject, $subjectPlaceholders);
        $body = strtr($template->body_html, $htmlPlaceholders);

        return new \App\Mail\CustomHtmlMail($subject, $body);
    }
}