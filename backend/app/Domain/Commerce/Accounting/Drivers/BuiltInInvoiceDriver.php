<?php

namespace App\Domain\Commerce\Accounting\Drivers;

use App\Domain\Commerce\Accounting\AccountingDriverInterface;
use App\Domain\Operations\IntegrationLogService;
use App\Models\Order;
use App\Models\Invoice;
use App\Support\StoreSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BuiltInInvoiceDriver implements AccountingDriverInterface
{
    private const INTEGRATION = 'built_in';

    public function __construct(
        private readonly StoreSettings $storeSettings,
        private readonly IntegrationLogService $integrationLogService
    ) {
    }

    public function sendOrder(Order $order): void
    {
        // 1. Check if built-in invoicing is enabled
        if (!config('accounting.drivers.built_in.enabled') && !$this->storeSettings->invoicingEnabled()) {
            return;
        }

        // 2. Prevent duplicate invoice generation for the same order
        $existingInvoice = Invoice::query()->where('order_id', $order->id)->first();
        if ($existingInvoice) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'order_sent_skipped',
                status: 'warning',
                order: $order,
                errorMessage: 'Faktura dla tego zamówienia została już wcześniej wygenerowana.'
            );
            return;
        }

        try {
            // 3. Collect configuration details
            $seller = [
                'name' => $this->storeSettings->invoiceSellerName(),
                'address' => $this->storeSettings->invoiceSellerAddress(),
                'nip' => $this->storeSettings->invoiceSellerNip(),
                'bank_account' => $this->storeSettings->invoiceSellerBankAccount(),
            ];

            $prefix = $this->storeSettings->invoiceNumberPrefix();
            $paymentDays = $this->storeSettings->invoicePaymentDays();

            $now = now();
            $year = $now->year;
            $month = str_pad($now->month, 2, '0', STR_PAD_LEFT);

            // Find next sequential number for current month/year
            $count = Invoice::query()
                ->whereYear('issue_date', $year)
                ->whereMonth('issue_date', $now->month)
                ->count();
            $nextSeq = $count + 1;
            $seqPadded = str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $invoiceNumber = "{$prefix}{$year}/{$month}/{$seqPadded}";
            $issueDate = $now;
            $dueDate = $now->copy()->addDays($paymentDays);

            // 4. Calculate invoice items, sums and tax rates
            $invoiceItems = [];
            $vatSummary = [];
            $totalNetSum = 0;
            $totalTaxSum = 0;
            $totalGrossSum = 0;

            foreach ($order->items as $item) {
                $product = $item->product;
                $variant = $item->variant;
                $vatRate = $variant ? ($variant->vat_rate ?? 23) : ($product ? ($product->vat_rate ?? 23) : 23);

                $totalGross = $item->total_amount;
                $totalNet = (int) round($totalGross / (1 + ($vatRate / 100)));
                $totalTax = $totalGross - $totalNet;

                $priceNet = (int) round($totalNet / $item->quantity);

                $invoiceItems[] = [
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'price_net' => $priceNet,
                    'vat_rate' => $vatRate,
                    'total_net' => $totalNet,
                    'total_gross' => $totalGross,
                ];

                $totalNetSum += $totalNet;
                $totalTaxSum += $totalTax;
                $totalGrossSum += $totalGross;

                // Group by VAT rate
                if (!isset($vatSummary[$vatRate])) {
                    $vatSummary[$vatRate] = ['net' => 0, 'tax' => 0, 'gross' => 0];
                }
                $vatSummary[$vatRate]['net'] += $totalNet;
                $vatSummary[$vatRate]['tax'] += $totalTax;
                $vatSummary[$vatRate]['gross'] += $totalGross;
            }

            // Add shipping as a separate line item if present
            if ($order->shipping_amount > 0) {
                $shippingGross = $order->shipping_amount;
                $shippingNet = (int) round($shippingGross / 1.23);
                $shippingTax = $shippingGross - $shippingNet;

                $invoiceItems[] = [
                    'name' => 'Koszt wysyłki - ' . ($order->shipping_method_name ?? 'Dostawa'),
                    'sku' => null,
                    'quantity' => 1,
                    'price_net' => $shippingNet,
                    'vat_rate' => 23,
                    'total_net' => $shippingNet,
                    'total_gross' => $shippingGross,
                ];

                $totalNetSum += $shippingNet;
                $totalTaxSum += $shippingTax;
                $totalGrossSum += $shippingGross;

                if (!isset($vatSummary[23])) {
                    $vatSummary[23] = ['net' => 0, 'tax' => 0, 'gross' => 0];
                }
                $vatSummary[23]['net'] += $shippingNet;
                $vatSummary[23]['tax'] += $shippingTax;
                $vatSummary[23]['gross'] += $shippingGross;
            }

            // Create temporary invoice instance for PDF generation
            $invoice = new Invoice([
                'number' => $invoiceNumber,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'total_amount' => $totalGrossSum,
                'tax_amount' => $totalTaxSum,
            ]);

            // 5. Generate PDF
            $pdf = Pdf::loadView('invoices.pdf', [
                'invoice' => $invoice,
                'order' => $order,
                'seller' => $seller,
                'items' => $invoiceItems,
                'vatSummary' => $vatSummary,
                'totals' => [
                    'net' => $totalNetSum,
                    'tax' => $totalTaxSum,
                    'gross' => $totalGrossSum,
                ],
            ]);

            $pdfContent = $pdf->output();

            // Save PDF file to secure private storage
            $cleanNumber = Str::slug(str_replace('/', '_', $invoiceNumber));
            $pdfPath = "invoices/{$year}/{$month}/{$cleanNumber}.pdf";
            Storage::disk('local')->put($pdfPath, $pdfContent);

            // 6. Persist to database
            $invoice->order_id = $order->id;
            $invoice->pdf_path = $pdfPath;
            $invoice->save();

            // 7. Record integration log
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'order_sent',
                status: 'success',
                order: $order,
                direction: 'outgoing',
                externalReference: $invoiceNumber,
                requestPayload: [
                    'seller' => $seller,
                    'prefix' => $prefix,
                    'invoice_number' => $invoiceNumber,
                ],
                responsePayload: [
                    'invoice_id' => $invoice->id,
                    'pdf_path' => $pdfPath,
                ]
            );

        } catch (\Exception $e) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'order_sent_failed',
                status: 'error',
                order: $order,
                direction: 'outgoing',
                errorMessage: $e->getMessage()
            );

            throw $e;
        }
    }
}
