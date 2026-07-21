<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminOrderExportController extends Controller
{
    public function export(): StreamedResponse
    {
        $this->ensureAdmin();

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if (! $handle) {
                return;
            }

            // Write UTF-8 BOM and use semicolon as delimiter for Excel compatibility in Poland
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Numer zamówienia',
                'Data złożenia',
                'Status zamówienia',
                'Status płatności',
                'Status realizacji',
                'Waluta',
                'Suma brutto',
                'Suma netto',
                'Kwota VAT',
                'Kwota rabatu',
                'Kwota wysyłki',
                'E-mail klienta',
                'Imię klienta',
                'Nazwisko klienta',
                'Telefon',
                'Chce fakturę?',
                'Nazwa firmy',
                'NIP',
                'Adres rozliczeniowy',
                'Adres dostawy',
                'Metoda dostawy',
                'Przewoźnik',
                'Numer śledzenia'
            ], ';');

            Order::query()
                ->orderBy('placed_at', 'desc')
                ->chunk(200, function ($orders) use ($handle): void {
                    foreach ($orders as $order) {
                        $billingAddress = '';
                        if (is_array($order->billing_address)) {
                            $billingAddress = implode(', ', array_filter([
                                $order->billing_address['street'] ?? $order->billing_address['line1'] ?? $order->billing_address['line_1'] ?? '',
                                $order->billing_address['postal_code'] ?? $order->billing_address['postcode'] ?? '',
                                $order->billing_address['city'] ?? '',
                                $order->billing_address['country'] ?? ''
                            ]));
                        }

                        $shippingAddress = '';
                        if (is_array($order->shipping_address)) {
                            $shippingAddress = implode(', ', array_filter([
                                $order->shipping_address['street'] ?? $order->shipping_address['line1'] ?? $order->shipping_address['line_1'] ?? '',
                                $order->shipping_address['postal_code'] ?? $order->shipping_address['postcode'] ?? '',
                                $order->shipping_address['city'] ?? '',
                                $order->shipping_address['country'] ?? ''
                            ]));
                        }

                        fputcsv($handle, [
                            $order->number,
                            optional($order->placed_at)->format('Y-m-d H:i:s'),
                            $order->status,
                            $order->payment_status,
                            $order->fulfillment_status,
                            $order->currency,
                            number_format($order->total_amount / 100, 2, '.', ''),
                            number_format($order->subtotal_amount / 100, 2, '.', ''),
                            number_format($order->tax_amount / 100, 2, '.', ''),
                            number_format($order->discount_amount / 100, 2, '.', ''),
                            number_format($order->shipping_amount / 100, 2, '.', ''),
                            $order->customer_email,
                            $order->customer_first_name,
                            $order->customer_last_name,
                            $order->customer_phone,
                            $order->wants_invoice ? 'Tak' : 'Nie',
                            $order->billing_company_name,
                            $order->billing_nip,
                            $billingAddress,
                            $shippingAddress,
                            $order->shipping_method_name,
                            $order->carrier,
                            $order->tracking_number,
                        ], ';');
                    }
                });

            fclose($handle);
        }, 'orders-export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }
}
