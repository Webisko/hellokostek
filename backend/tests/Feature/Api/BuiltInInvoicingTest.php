<?php

namespace Tests\Feature\Api;

use App\Domain\Commerce\Accounting\Drivers\BuiltInInvoiceDriver;
use App\Mail\OrderPaidCustomerMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\StoreSetting;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuiltInInvoicingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();

        // Enable built-in invoicing in config and store settings
        config([
            'accounting.drivers.built_in.enabled' => true,
        ]);

        $setting = StoreSetting::query()->first() ?? StoreSetting::query()->create([
            'store_name' => 'Test Store',
            'currency' => 'PLN',
            'free_shipping_threshold' => 20000,
            'wholesale_minimum_regular_price_multiplier' => 0.7,
            'allow_guest_checkout' => true,
            'mail_from_name' => 'Test sender',
            'mail_from_address' => 'sender@test.com',
            'metadata' => [
                'invoicing_enabled' => true,
                'invoice_number_prefix' => 'FV/',
                'invoice_seller_name' => 'Seller Sp. z o.o.',
                'invoice_seller_address' => 'ul. Wiejska 1, Warszawa',
                'invoice_seller_nip' => '1234567890',
                'invoice_seller_bank_account' => 'PL00123456789012345678901234',
                'invoice_payment_days' => 14,
            ]
        ]);
    }

    public function test_built_in_driver_generates_invoice_record_and_pdf_file(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-10001',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 12300,
            'customer_email' => 'buyer@test.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'billing_address' => [
                'line_1' => 'ul. Koszykowa 12',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country' => 'PL',
            ]
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => 'Produkt Testowy',
            'sku' => 'PROD-1',
            'quantity' => 1,
            'unit_price_amount' => 12300,
            'total_amount' => 12300,
            'product_type' => 'physical',
        ]);

        $driver = app(BuiltInInvoiceDriver::class);
        $driver->sendOrder($order);

        // Verify record in database
        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->id,
            'total_amount' => 12300,
        ]);

        $invoice = Invoice::query()->where('order_id', $order->id)->firstOrFail();
        $expectedNumber = 'FV/' . now()->year . '/' . str_pad(now()->month, 2, '0', STR_PAD_LEFT) . '/0001';
        $this->assertEquals($expectedNumber, $invoice->number);

        // Verify PDF file exists in fake storage
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);
    }

    public function test_invoice_number_sequences_increment_correctly(): void
    {
        $order1 = Order::query()->create([
            'number' => 'ORD-20001',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 10000,
            'customer_email' => 'buyer@test.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
        ]);
        $order2 = Order::query()->create([
            'number' => 'ORD-20002',
            'status' => 'placed',
            'payment_status' => 'awaiting_payment',
            'currency' => 'PLN',
            'total_amount' => 20000,
            'customer_email' => 'buyer@test.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
        ]);

        OrderItem::query()->create([
            'order_id' => $order1->id,
            'name' => 'Item 1',
            'sku' => 'I1',
            'quantity' => 1,
            'unit_price_amount' => 10000,
            'total_amount' => 10000,
            'product_type' => 'physical',
        ]);

        OrderItem::query()->create([
            'order_id' => $order2->id,
            'name' => 'Item 2',
            'sku' => 'I2',
            'quantity' => 1,
            'unit_price_amount' => 20000,
            'total_amount' => 20000,
            'product_type' => 'physical',
        ]);

        $driver = app(BuiltInInvoiceDriver::class);
        
        $driver->sendOrder($order1);
        $driver->sendOrder($order2);

        $invoice1 = Invoice::query()->where('order_id', $order1->id)->firstOrFail();
        $invoice2 = Invoice::query()->where('order_id', $order2->id)->firstOrFail();

        $prefix = 'FV/' . now()->year . '/' . str_pad(now()->month, 2, '0', STR_PAD_LEFT) . '/';
        $this->assertEquals($prefix . '0001', $invoice1->number);
        $this->assertEquals($prefix . '0002', $invoice2->number);
    }

    public function test_order_paid_mail_attaches_pdf_invoice(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-30001',
            'status' => 'placed',
            'payment_status' => 'paid',
            'currency' => 'PLN',
            'total_amount' => 12300,
            'customer_email' => 'buyer@test.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
        ]);

        $invoice = Invoice::query()->create([
            'order_id' => $order->id,
            'number' => 'FV/2026/07/0001',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'total_amount' => 12300,
            'tax_amount' => 2300,
            'pdf_path' => 'invoices/2026/07/fv_2026_07_0001.pdf',
        ]);

        Storage::disk('local')->put($invoice->pdf_path, 'mock pdf content');

        $mailable = new OrderPaidCustomerMail($order);

        $attachments = $mailable->attachments();
        $this->assertCount(1, $attachments);
        $this->assertEquals('Faktura_FV_2026_07_0001.pdf', $attachments[0]->as);
    }
}
