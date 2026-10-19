<?php

namespace App\Domain\Commerce\Checkout;

use App\Domain\Communication\TransactionalEmailService;
use App\Domain\Commerce\Pricing\Data\QuoteResult;
use App\Domain\Commerce\Fulfillment\OrderFulfillmentPlanner;
use App\Domain\Commerce\Pricing\PricingEngine;
use App\Domain\Customers\CustomerAccountService;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutOrderService
{
    public function __construct(
        private readonly CheckoutDataResolver $checkoutDataResolver,
        private readonly OrderFulfillmentPlanner $orderFulfillmentPlanner,
        private readonly PricingEngine $pricingEngine,
        private readonly CustomerAccountService $customerAccountService,
        private readonly TransactionalEmailService $transactionalEmailService,
        private readonly \App\Domain\Commerce\Payments\PaymentSessionService $paymentSessionService,
    ) {
    }

    /**
     * @return array{order: Order, quote: QuoteResult, payment: array<string, mixed>|null}
     */
    public function createDraft(array $validated): array
    {
        return $this->create($validated, false);
    }

    /**
     * @return array{order: Order, quote: QuoteResult, payment: array<string, mixed>|null}
     */
    public function place(array $validated): array
    {
        return $this->create($validated, true);
    }

    /**
     * @return array{order: Order, quote: QuoteResult, payment: array<string, mixed>|null}
     */
    private function create(array $validated, bool $placeOrder): array
    {
        $resolvedCheckout = $this->checkoutDataResolver->resolve($validated, requireFreshInventory: $placeOrder);
        $quoteResult = $this->pricingEngine->calculate($resolvedCheckout->quote);
        $customerUser = $this->customerAccountService->findByEmail($validated['customer']['email']);
        $selectedPaymentMethod = $placeOrder
            ? $this->resolvePaymentMethod($validated['payment_method'] ?? null, $quoteResult)
            : null;
        $paymentSummary = $placeOrder
            ? $this->buildPaymentSummary($selectedPaymentMethod)
            : null;

        /** @var Order $order */
        $order = DB::transaction(function () use ($validated, $resolvedCheckout, &$quoteResult, $placeOrder, $selectedPaymentMethod, $paymentSummary, $customerUser): Order {
            // Determine if B2C and destination country is EU other than Poland
            $shippingAddress = $validated['shipping_address'] ?? ($validated['billing_address'] ?? null);
            $destinationCountry = \App\Support\VatOssHelper::resolveCountryCode($shippingAddress);
            $isB2c = empty($validated['customer']['nip']);

            $isReverseCharge = false;
            $viesMetadata = [];

            if (!$isB2c && \App\Support\VatOssHelper::isEuCountryOtherThanPoland($destinationCountry)) {
                $nip = $validated['customer']['nip'] ?? '';
                if (!blank($nip)) {
                    $viesResult = app(\App\Support\ViesValidator::class)->validate($nip, $destinationCountry);
                    if ($viesResult['isValid']) {
                        $isReverseCharge = true;
                        $viesMetadata = [
                            'vies_status' => $viesResult['status'],
                            'vies_message' => $viesResult['message'],
                            'vies_trader_name' => $viesResult['traderName'],
                            'vies_trader_address' => $viesResult['traderAddress'],
                            'vies_request_date' => now()->toIso8601String(),
                        ];
                    }
                }
            }

            $vatRateOverride = null;
            if (!$isReverseCharge && $isB2c && \App\Support\VatOssHelper::isEuCountryOtherThanPoland($destinationCountry)) {
                $vatRateOverride = \App\Support\VatOssHelper::getVatRateForCountry($destinationCountry);
            }

            // Precalculate taxes for each item
            $itemTaxes = [];
            $vatRates = [];
            $itemUnitPrices = [];
            $itemRegularPrices = [];
            $totalItemsTax = 0;
            $adjustedItems = [];

            // UK B2C rules check
            $isUkVatApplicable = false;
            if ($isB2c && $destinationCountry === 'GB') {
                $totalInGbp = \App\Support\TaxHelper::convertAmount($quoteResult->totalAmount, 'GBP');
                if ($totalInGbp <= 13500) { // <= 135.00 GBP
                    $isUkVatApplicable = true;
                }
            }

            foreach ($resolvedCheckout->quoteItems as $index => $quoteItem) {
                $product = \App\Models\Product::find($quoteItem->productId);
                $vatRate = 23;
                if ($quoteItem->productVariantId) {
                    $variant = \App\Models\ProductVariant::find($quoteItem->productVariantId);
                    $vatRate = $variant ? ($variant->vat_rate ?? 23) : 23;
                } else {
                    $vatRate = $product ? ($product->vat_rate ?? 23) : 23;
                }

                $originalVatRate = $vatRate;

                // Apply VAT overrides based on country and value
                if ($isReverseCharge) {
                    $vatRate = 0;
                } elseif ($vatRateOverride !== null) {
                    $vatRate = $vatRateOverride;
                } elseif ($isB2c && $destinationCountry === 'GB') {
                    $vatRate = $isUkVatApplicable ? 20 : 0;
                } elseif ($isB2c && $destinationCountry === 'NO') {
                    $lineTotal = $quoteResult->items[$index]['line_total_after_coupon_amount'];
                    $quantity = $quoteItem->quantity;
                    $unitPrice = (int) ($lineTotal / $quantity);
                    $unitPriceInNok = \App\Support\TaxHelper::convertAmount($unitPrice, 'NOK');
                    
                    if ($unitPriceInNok <= 300000) { // <= 3000 NOK
                        $vatRate = 25;
                    } else {
                        $vatRate = 0;
                    }
                }
                $vatRates[$index] = $vatRate;

                $lineTotal = $quoteResult->items[$index]['line_total_after_coupon_amount'];
                $itemUnitPrice = $quoteItem->unitPriceAmount;
                $itemRegularPrice = $quoteItem->regularUnitPriceAmount;
                
                if ($isReverseCharge) {
                    $calcOriginalVatRate = $originalVatRate === 99 ? 0 : $originalVatRate;
                    $lineNet = (int) round($lineTotal / (1 + ($calcOriginalVatRate / 100)));
                    $lineTax = 0;
                    $lineTotal = $lineNet;
                    
                    // Calculate net unit prices
                    $itemUnitPrice = (int) round($itemUnitPrice / (1 + ($calcOriginalVatRate / 100)));
                    $itemRegularPrice = (int) round($itemRegularPrice / (1 + ($calcOriginalVatRate / 100)));
                } else {
                    $calcVatRate = $vatRate === 99 ? 0 : $vatRate;
                    $lineNet = (int) round($lineTotal / (1 + ($calcVatRate / 100)));
                    $lineTax = $lineTotal - $lineNet;
                }
                
                $itemTaxes[$index] = $lineTax;
                $totalItemsTax += $lineTax;
                $itemUnitPrices[$index] = $itemUnitPrice;
                $itemRegularPrices[$index] = $itemRegularPrice;

                $adjustedItem = $quoteResult->items[$index];
                $adjustedItem['line_total_after_coupon_amount'] = $lineTotal;
                $adjustedItems[$index] = $adjustedItem;
            }

            // Calculate shipping tax
            $shippingAmount = $quoteResult->shippingAmount;
            $shippingVatRate = 23;
            if ($isReverseCharge) {
                $shippingVatRate = 0;
                $shippingNet = (int) round($shippingAmount / (1 + (23 / 100)));
                $shippingAmount = $shippingNet;
                $shippingTax = 0;
            } else {
                if ($vatRateOverride !== null) {
                    $shippingVatRate = $vatRateOverride;
                } elseif ($isB2c && $destinationCountry === 'GB') {
                    $shippingVatRate = $isUkVatApplicable ? 20 : 0;
                } elseif ($isB2c && $destinationCountry === 'NO') {
                    // If there's any item with 25% VAT, shipping is also 25% VAT
                    $hasNoVatItem = collect($vatRates)->contains(25);
                    $shippingVatRate = $hasNoVatItem ? 25 : 0;
                }
                $calcShippingVatRate = $shippingVatRate === 99 ? 0 : $shippingVatRate;
                $shippingNet = (int) round($shippingAmount / (1 + ($calcShippingVatRate / 100)));
                $shippingTax = $shippingAmount - $shippingNet;
            }

            $totalOrderTax = $totalItemsTax + $shippingTax;

            if ($isReverseCharge) {
                $netSubtotalAfterCoupon = array_sum(collect($adjustedItems)->pluck('line_total_after_coupon_amount')->toArray());
                $netTotalAmount = max(0, $netSubtotalAfterCoupon - $quoteResult->loyaltyDiscountAmount - $quoteResult->wholesaleDiscountAmount + $shippingAmount + $quoteResult->importDutyAmount);

                $quoteResult = new \App\Domain\Commerce\Pricing\Data\QuoteResult(
                    items: $adjustedItems,
                    subtotalAmount: $netSubtotalAfterCoupon,
                    couponDiscountAmount: $quoteResult->couponDiscountAmount,
                    loyaltyDiscountAmount: $quoteResult->loyaltyDiscountAmount,
                    wholesaleDiscountAmount: $quoteResult->wholesaleDiscountAmount,
                    shippingAmount: $shippingAmount,
                    totalAmount: $netTotalAmount,
                    freeShippingApplied: $quoteResult->freeShippingApplied,
                    appliedCouponCode: $quoteResult->appliedCouponCode,
                    shippingMethodCode: $quoteResult->shippingMethodCode,
                    availablePaymentMethods: $quoteResult->availablePaymentMethods,
                    importDutyAmount: $quoteResult->importDutyAmount
                );
            }

            $order = Order::query()->create([
                'number' => $this->generateOrderNumber($placeOrder),
                'user_id' => $customerUser?->id,
                'coupon_id' => $resolvedCheckout->coupon?->id,
                'status' => $placeOrder ? 'placed' : 'draft',
                'payment_status' => $placeOrder ? $this->resolvePaymentStatus($selectedPaymentMethod) : 'pending',
                'fulfillment_status' => 'pending',
                'currency' => $resolvedCheckout->currency,
                'customer_segment' => $resolvedCheckout->quote->customerSegment,
                'customer_email' => $validated['customer']['email'],
                'customer_first_name' => $validated['customer']['first_name'],
                'customer_last_name' => $validated['customer']['last_name'],
                'customer_phone' => $validated['customer']['phone'] ?? null,
                'billing_company_name' => $validated['customer']['company_name'] ?? null,
                'billing_nip' => $validated['customer']['nip'] ?? null,
                'wants_invoice' => (bool) ($validated['customer']['wants_invoice'] ?? false),
                'is_privileged_entrepreneur' => (bool) ($validated['customer']['is_privileged_entrepreneur'] ?? false),
                'subtotal_amount' => $quoteResult->subtotalAmount,
                'discount_amount' => $quoteResult->couponDiscountAmount + $quoteResult->loyaltyDiscountAmount + $quoteResult->wholesaleDiscountAmount,
                'shipping_amount' => $quoteResult->shippingAmount,
                'tax_amount' => $totalOrderTax,
                'total_amount' => $quoteResult->totalAmount,
                'shipping_method_code' => $resolvedCheckout->shippingMethodCode,
                'shipping_method_name' => $resolvedCheckout->shippingMethodName,
                'billing_address' => $validated['billing_address'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? ($validated['billing_address'] ?? null),
                'placed_at' => $placeOrder ? now() : null,
                'notes' => $validated['notes'] ?? null,
                'metadata' => array_filter([
                    $placeOrder ? 'checkout_source' : 'draft_source' => $placeOrder ? 'api.checkout_place' : 'api.checkout_draft',
                    'quote' => $quoteResult->toArray(),
                    'payment' => $paymentSummary,
                    'delivery_point' => $this->normalizeDeliveryPoint($validated['delivery_point'] ?? null),
                    'terms_acceptance' => $placeOrder ? [
                        'accepted' => true,
                        'version' => app(\App\Support\StoreSettings::class)->termsVersion(),
                        'accepted_at' => now()->toIso8601String(),
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'consent_text' => 'Oświadczam, że zapoznałem się z Regulaminem sklepu i akceptuję jego postanowienia.',
                    ] : null,
                    'digital_consent' => (isset($validated['digital_consent']) || isset($validated['customer']['digital_consent'])) &&
                        filter_var($validated['digital_consent'] ?? $validated['customer']['digital_consent'] ?? false, FILTER_VALIDATE_BOOLEAN)
                        ? [
                            'accepted' => true,
                            'accepted_at' => now()->toIso8601String(),
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'consent_text' => 'Wyrażam zgodę na dostarczenie treści cyfrowych natychmiast i przyjmuję do wiadomości, że tracę prawo do odstąpienia od umowy.',
                        ] : null,
                    'marketing_acceptance' => $placeOrder && (
                        filter_var($validated['marketing_accepted'] ?? $validated['customer']['marketing_accepted'] ?? false, FILTER_VALIDATE_BOOLEAN)
                    ) ? [
                        'accepted' => true,
                        'accepted_at' => now()->toIso8601String(),
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'consent_text' => 'Wyrażam zgodę na otrzymywanie informacji handlowych drogą elektroniczną (Newsletter).',
                    ] : null,
                    'vies_status' => $viesMetadata['vies_status'] ?? null,
                    'vies_message' => $viesMetadata['vies_message'] ?? null,
                    'vies_trader_name' => $viesMetadata['vies_trader_name'] ?? null,
                    'vies_trader_address' => $viesMetadata['vies_trader_address'] ?? null,
                    'vies_request_date' => $viesMetadata['vies_request_date'] ?? null,
                ]),
            ]);

            foreach ($resolvedCheckout->quoteItems as $index => $quoteItem) {
                $product = \App\Models\Product::find($quoteItem->productId);
                $variant = $quoteItem->productVariantId 
                    ? \App\Models\ProductVariant::find($quoteItem->productVariantId) 
                    : null;
                
                if ($placeOrder) {
                    if ($variant && $variant->manages_stock) {
                        $lockedVariant = $selectedPaymentMethod === 'bank_transfer'
                            ? \App\Models\ProductVariant::query()->lockForUpdate()->find($variant->id)
                            : $variant;

                        if (! $lockedVariant || $lockedVariant->stock_quantity < $quoteItem->quantity) {
                            throw ValidationException::withMessages([
                                'items.' . $index . '.quantity' => 'Brak wystarczającego stanu magazynowego dla wybranego wariantu: ' . $variant->sku,
                            ]);
                        }

                        if ($selectedPaymentMethod === 'bank_transfer') {
                            $newStock = max(0, (int) $lockedVariant->stock_quantity - (int) $quoteItem->quantity);
                            $lockedVariant->update(['stock_quantity' => $newStock]);

                            if (str_ends_with((string) $lockedVariant->sku, '-OR') && $newStock <= 0) {
                                if ($product) {
                                    $prodMeta = is_array($product->metadata) ? $product->metadata : [];
                                    $prodMeta['original_available'] = false;
                                    $product->updateQuietly(['metadata' => $prodMeta]);
                                }

                                $competingOrders = Order::query()
                                    ->where('id', '!=', $order->id)
                                    ->where('payment_status', '!=', 'paid')
                                    ->where('status', '!=', 'cancelled')
                                    ->whereHas('items', fn ($q) => $q->where('product_variant_id', $lockedVariant->id))
                                    ->with('paymentTransactions')
                                    ->get();

                                foreach ($competingOrders as $competingOrder) {
                                    foreach ($competingOrder->paymentTransactions as $tx) {
                                        if ($tx->provider === 'stripe' && str_starts_with((string) $tx->external_session_id, 'cs_')) {
                                            try {
                                                app(\App\Domain\Commerce\Payments\StripeApiClient::class)
                                                    ->getClient()
                                                    ->checkout
                                                    ->sessions
                                                    ->expire($tx->external_session_id);
                                            } catch (\Throwable $e) {
                                            }
                                        }
                                    }
                                    $compMeta = $competingOrder->metadata ?? [];
                                    $compMeta['cancelled_reason'] = 'Praca oryginalna została zarezerwowana w zamówieniu ' . $order->number;
                                    $competingOrder->forceFill([
                                        'status' => 'cancelled',
                                        'payment_status' => 'failed',
                                        'metadata' => $compMeta,
                                    ])->saveQuietly();
                                }
                            }

                            if ($product) {
                                \App\Models\Product::flushCatalogCache($product->slug);
                            }
                        }
                    } elseif ($product && $product->isBundle()) {
                        foreach ($product->bundleItems as $bundleItem) {
                            if ($bundleItem->product_variant_id) {
                                $component = $bundleItem->variant;
                                if ($component && $component->manages_stock) {
                                    $neededQty = $bundleItem->quantity * $quoteItem->quantity;
                                    if ($component->stock_quantity < $neededQty) {
                                        throw ValidationException::withMessages([
                                            'items.' . $index . '.quantity' => 'Brak wystarczającego stanu magazynowego wariantu ' . $component->sku . ' dla zestawu: ' . $product->name,
                                        ]);
                                    }
                                }
                            } else {
                                $component = $bundleItem->product;
                                if ($component && $component->manages_stock) {
                                    $neededQty = $bundleItem->quantity * $quoteItem->quantity;
                                    if ($component->stock_quantity < $neededQty) {
                                        throw ValidationException::withMessages([
                                            'items.' . $index . '.quantity' => 'Brak wystarczającego stanu magazynowego składnika ' . $component->name . ' dla zestawu: ' . $product->name,
                                        ]);
                                    }
                                }
                            }
                        }
                    } elseif ($product && $product->manages_stock) {
                        if ($product->stock_quantity < $quoteItem->quantity) {
                            throw ValidationException::withMessages([
                                'items.' . $index . '.quantity' => 'Brak wystarczającego stanu magazynowego dla produktu: ' . $product->name,
                            ]);
                        }
                        if ($selectedPaymentMethod === 'bank_transfer') {
                            $newStock = max(0, (int) $product->stock_quantity - (int) $quoteItem->quantity);
                            $product->updateQuietly(['stock_quantity' => $newStock]);
                            \App\Models\Product::flushCatalogCache($product->slug);
                        }
                    }
                }

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $quoteItem->productId,
                    'product_variant_id' => $quoteItem->productVariantId,
                    'product_type' => $quoteItem->productType->value,
                    'sku' => $variant ? $variant->sku : ($product ? $product->sku : null),
                    'name' => $quoteItem->name,
                    'quantity' => $quoteItem->quantity,
                    'unit_price_amount' => $itemUnitPrices[$index],
                    'regular_unit_price_amount' => $itemRegularPrices[$index],
                    'discount_amount' => $quoteResult->items[$index]['coupon_discount_amount'],
                    'tax_amount' => $itemTaxes[$index],
                    'total_amount' => $quoteResult->items[$index]['line_total_after_coupon_amount'],
                    'metadata' => [
                        'slug' => $quoteItem->slug,
                        'vat_rate' => $vatRates[$index] ?? 23,
                    ],
                ]);
            }

            if ($placeOrder && $selectedPaymentMethod === 'bank_transfer') {
                $reservationDays = app(\App\Support\StoreSettings::class)->bankTransferReservationDays();
                $orderMeta = $order->metadata ?? [];
                $orderMeta['stock_deducted'] = true;
                $orderMeta['reservation_days'] = $reservationDays;
                $orderMeta['reservation_expires_at'] = now()->addWeekdays($reservationDays)->toIso8601String();
                $order->forceFill(['metadata' => $orderMeta])->saveQuietly();
            }

            if ($placeOrder && $customerUser) {
                $this->customerAccountService->syncProfile($customerUser);
            }

            if ($placeOrder) {
                $this->orderFulfillmentPlanner->ensurePlanned($order->loadMissing(['items.product', 'fulfillmentActions']));
            }

            return $order->load('items');
        });
        if ($placeOrder) {
            if (in_array($selectedPaymentMethod, ['stripe', 'przelewy24'], true)) {
                try {
                    $transaction = $this->paymentSessionService->initiate($order);
                    if (!empty($transaction->redirect_url)) {
                        $paymentSummary['redirect_url'] = $transaction->redirect_url;
                        $paymentSummary['payment_url'] = $transaction->redirect_url;
                    }
                } catch (\Throwable $e) {
                    // Gateway error or configuration required - keep redirect_url null
                }
            }

            $order->unsetRelation('latestPaymentTransaction');
            $order->load('latestPaymentTransaction');
            $this->transactionalEmailService->sendOrderPlacedEmails($order);
        }

        return [
            'order' => $order,
            'quote' => $quoteResult,
            'payment' => $paymentSummary,
        ];
    }

    private function resolvePaymentMethod(?string $paymentMethod, QuoteResult $quoteResult): string
    {
        if (! $paymentMethod) {
            throw ValidationException::withMessages([
                'payment_method' => 'Wybierz metodę płatności.',
            ]);
        }

        if (! in_array($paymentMethod, $quoteResult->availablePaymentMethods, true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Wybrana metoda płatności nie jest dostępna dla tego koszyka.',
            ]);
        }

        return $paymentMethod;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPaymentSummary(string $paymentMethod): array
    {
        return match ($paymentMethod) {
            'przelewy24' => [
                'provider' => 'przelewy24',
                'status' => 'pending_gateway',
                'requires_redirect' => true,
                'redirect_url' => null,
                'next_action' => 'create_przelewy24_transaction',
            ],
            'stripe' => [
                'provider' => 'stripe',
                'status' => 'pending_gateway',
                'requires_redirect' => true,
                'redirect_url' => null,
                'next_action' => 'create_stripe_transaction',
            ],
            'bank_transfer' => [
                'provider' => 'bank_transfer',
                'status' => 'pending_transfer',
                'requires_redirect' => false,
                'redirect_url' => null,
                'next_action' => 'await_wire_transfer',
            ],
            'cod' => [
                'provider' => 'cod',
                'status' => 'pending_collection',
                'requires_redirect' => false,
                'redirect_url' => null,
                'next_action' => 'await_shipment_confirmation',
            ],
            default => [
                'provider' => $paymentMethod,
                'status' => 'pending',
                'requires_redirect' => false,
                'redirect_url' => null,
                'next_action' => null,
            ],
        };
    }

    private function resolvePaymentStatus(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'przelewy24', 'stripe', 'bank_transfer' => 'awaiting_payment',
            default => 'pending',
        };
    }



    private function generateOrderNumber(bool $placeOrder): string
    {
        $prefix = $placeOrder ? 'ORD' : 'DRAFT';

        return $prefix . '-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    /**
     * @param  array<string, mixed>|null  $deliveryPoint
     * @return array<string, string>|null
     */
    private function normalizeDeliveryPoint(?array $deliveryPoint): ?array
    {
        if (! is_array($deliveryPoint)) {
            return null;
        }

        $normalized = collect([
            'id' => Arr::get($deliveryPoint, 'id'),
            'name' => Arr::get($deliveryPoint, 'name'),
            'address' => Arr::get($deliveryPoint, 'address'),
            'postal_code' => Arr::get($deliveryPoint, 'postal_code'),
            'city' => Arr::get($deliveryPoint, 'city'),
        ])
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => (string) $value)
            ->all();

        return $normalized !== [] ? $normalized : null;
    }
}
