<?php

namespace App\Http\Controllers\Api;

use App\Domain\Commerce\Checkout\CheckoutOrderService;
use App\Domain\Commerce\Enums\CustomerSegment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutPlaceController extends Controller
{
    public function __construct(
        private readonly CheckoutOrderService $checkoutOrderService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.slug' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'customer_segment' => ['nullable', 'string', Rule::in(array_map(
                static fn (CustomerSegment $segment) => $segment->value,
                CustomerSegment::cases(),
            ))],
            'shipping_method_code' => ['nullable', 'string'],
            'coupon_code' => ['nullable', 'string'],
            'payment_method' => ['required', 'string'],
            'customer' => ['required', 'array'],
            'customer.email' => ['required', 'email:rfc'],
            'customer.first_name' => ['required', 'string', 'max:255'],
            'customer.last_name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:64'],
            'customer.wants_invoice' => ['nullable', 'boolean'],
            'customer.company_name' => ['nullable', 'string', 'max:255'],
            'customer.nip' => ['nullable', 'string', 'max:64'],
            'billing_address' => ['nullable', 'array'],
            'shipping_address' => ['nullable', 'array'],
            'delivery_point' => ['nullable', 'array'],
            'delivery_point.id' => ['nullable', 'string', 'max:64'],
            'delivery_point.name' => ['nullable', 'string', 'max:255'],
            'delivery_point.address' => ['nullable', 'string', 'max:255'],
            'delivery_point.postal_code' => ['nullable', 'string', 'max:32'],
            'delivery_point.city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms_accepted' => ['nullable', 'boolean'],
            'customer.terms_accepted' => ['nullable', 'boolean'],
            'digital_consent' => ['nullable', 'boolean'],
            'customer.digital_consent' => ['nullable', 'boolean'],
            'marketing_accepted' => ['nullable', 'boolean'],
            'customer.marketing_accepted' => ['nullable', 'boolean'],
            'customer.is_privileged_entrepreneur' => ['nullable', 'boolean'],
            'draft_number' => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $customer = $request->input('customer', []);
            $wantsInvoice = filter_var($customer['wants_invoice'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Terms and conditions acceptance check
            $termsAccepted = filter_var($request->input('terms_accepted') ?? $customer['terms_accepted'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (!$termsAccepted) {
                $validator->errors()->add('terms_accepted', 'Zaakceptowanie regulaminu sklepu jest wymagane.');
            }

            // Digital consent check if cart has digital products
            $items = $request->input('items', []);
            $slugs = array_map(static fn (array $item) => $item['slug'] ?? '', $items);
            $hasDigitalProducts = \App\Models\Product::query()
                ->whereIn('slug', $slugs)
                ->where('type', \App\Domain\Commerce\Enums\ProductType::Digital->value)
                ->exists();

            if ($hasDigitalProducts) {
                $digitalConsent = filter_var($request->input('digital_consent') ?? $customer['digital_consent'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if (!$digitalConsent) {
                    $validator->errors()->add('digital_consent', 'Wyrażenie zgody na dostarczenie treści cyfrowych natychmiast (zrzeczenie się prawa do odstąpienia od umowy) jest wymagane w przypadku zakupu produktów cyfrowych.');
                }
            }

            if ($wantsInvoice) {
                if (blank($customer['company_name'] ?? null)) {
                    $validator->errors()->add('customer.company_name', 'Nazwa firmy jest wymagana, jeśli chcesz otrzymać fakturę.');
                }
                if (blank($customer['nip'] ?? null)) {
                    $validator->errors()->add('customer.nip', 'Numer NIP jest wymagany, jeśli chcesz otrzymać fakturę.');
                }

                $billingAddress = $request->input('billing_address', []);
                if (blank($billingAddress)) {
                    $validator->errors()->add('billing_address', 'Adres rozliczeniowy (billingowy) jest wymagany, jeśli chcesz otrzymać fakturę.');
                } else {
                    $country = strtoupper(trim($billingAddress['country_code'] ?? $billingAddress['country'] ?? ''));
                    if (blank($country)) {
                        $validator->errors()->add('billing_address.country_code', 'Kraj jest wymagany w adresie rozliczeniowym.');
                    } elseif ($country === 'PL' || $country === 'POLSKA' || $country === 'POL') {
                        $nip = $customer['nip'] ?? '';
                        if (!blank($nip) && !\App\Support\B2bValidator::isValidPolishNip($nip)) {
                            $validator->errors()->add('customer.nip', 'Podany numer NIP dla Polski jest niepoprawny.');
                        }
                    } elseif (\App\Support\VatOssHelper::isEuCountryOtherThanPoland($country)) {
                        $nip = $customer['nip'] ?? '';
                        if (!blank($nip)) {
                            $viesResult = app(\App\Support\ViesValidator::class)->validate($nip, $country);
                            if (!$viesResult['isValid']) {
                                $validator->errors()->add('customer.nip', $viesResult['message']);
                            }
                        }
                    }
                }
            }
        });

        $validated = $validator->validate();

        if (!app(\App\Support\StoreSettings::class)->allowGuestCheckout() && !$request->user()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'customer' => ['Zakupy jako gość są wyłączone. Zaloguj się lub utwórz konto.'],
            ]);
        }

        $result = $this->checkoutOrderService->place($validated);
        /** @var \App\Models\Order $order */
        $order = $result['order'];

        $draftNumber = $request->input('draft_number');
        if ($draftNumber) {
            $draft = \App\Models\Order::query()->where('number', $draftNumber)->where('status', 'draft')->first();
            if ($draft) {
                $draftMetadata = $draft->metadata ?? [];
                $draftMetadata['converted_to_order_number'] = $order->number;
                $draftMetadata['converted_at'] = now()->toIso8601String();

                $draft->forceFill([
                    'status' => 'converted',
                    'metadata' => $draftMetadata,
                ])->save();
            }
        }

        return response()->json([
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'fulfillment_status' => $order->fulfillment_status,
                    'customer_email' => $order->customer_email,
                    'shipping_method_code' => $order->shipping_method_code,
                    'shipping_method_name' => $order->shipping_method_name,
                    'subtotal_amount' => $order->subtotal_amount,
                    'discount_amount' => $order->discount_amount,
                    'shipping_amount' => $order->shipping_amount,
                    'total_amount' => $order->total_amount,
                    'items_count' => $order->items->count(),
                    'wants_invoice' => $order->wants_invoice,
                    'billing_company_name' => $order->billing_company_name,
                    'billing_nip' => $order->billing_nip,
                    'delivery_point' => data_get($order->metadata, 'delivery_point'),
                    'placed_at' => optional($order->placed_at)->toIso8601String(),
                ],
                'quote' => $result['quote']->toArray(),
                'payment' => $result['payment'],
            ],
        ], 201);
    }
}