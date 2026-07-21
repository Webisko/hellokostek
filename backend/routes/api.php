<?php

use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\B2bGusController;
use App\Http\Controllers\Api\AccountMeController;
use App\Http\Controllers\Api\AccountOrdersController;
use App\Http\Controllers\Api\AnalyticsEventIngestController;
use App\Http\Controllers\Api\Auth\CustomerForgotPasswordController;
use App\Http\Controllers\Api\Auth\CustomerLoginController;
use App\Http\Controllers\Api\Auth\CustomerLogoutController;
use App\Http\Controllers\Api\Auth\CustomerRegisterController;
use App\Http\Controllers\Api\Auth\CustomerResetPasswordController;
use App\Http\Controllers\Api\CheckoutDraftController;
use App\Http\Controllers\Api\CheckoutPlaceController;
use App\Http\Controllers\Api\CheckoutPaymentSessionController;
use App\Http\Controllers\Api\CheckoutOrderShowController;
use App\Http\Controllers\Api\ContentMapController;
use App\Http\Controllers\Api\ContentPageIndexController;
use App\Http\Controllers\Api\ContentPageShowController;
use App\Http\Controllers\Api\FaqIndexController;
use App\Http\Controllers\Api\GalleryArtworkController;
use App\Http\Controllers\Api\GoogleReviewsController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\StoreSettingsController;
use App\Http\Controllers\Api\InventoryShowController;
use App\Http\Controllers\Api\Przelewy24PaymentCallbackController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\RedirectResolveController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\EmailVerificationResendController;
use App\Http\Controllers\Api\CustomerAddressController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\OrderReturnController;
use App\Http\Controllers\Api\CookieConsentController;
use App\Http\Controllers\Api\BackInStockSubscribeController;
use App\Http\Controllers\Api\CartController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContactInquiryController;
use App\Http\Controllers\Api\QuestionnaireSubmissionController;

use App\Http\Controllers\Api\SiteReviewsController;

Route::get('/health', HealthController::class)->name('api.health');
Route::get('/store/settings', StoreSettingsController::class)->name('api.store.settings');
Route::get('/reviews/google', GoogleReviewsController::class)->name('api.reviews.google');
Route::get('/reviews/site', SiteReviewsController::class)->name('api.reviews.site');
Route::get('/catalog', [CatalogController::class, 'index'])->name('api.catalog.index');
Route::get('/catalog/search/suggest', [CatalogController::class, 'suggest'])->name('api.catalog.search.suggest');
Route::get('/catalog/products/{slug}', [CatalogController::class, 'show'])->name('api.catalog.show');
Route::get('/catalog/products/{slug}/recommendations', [CatalogController::class, 'recommendations'])->name('api.catalog.products.recommendations');
Route::get('/catalog/products/{slug}/reviews', [ProductReviewController::class, 'index'])->name('api.catalog.products.reviews.index');
Route::get('/inventory/{sku}', InventoryShowController::class)->name('api.inventory.show');
Route::get('/content/map', ContentMapController::class)->name('api.content.map');
Route::get('/content/pages', ContentPageIndexController::class)->name('api.content.pages.index');
Route::get('/content/pages/{slug}', ContentPageShowController::class)->name('api.content.pages.show');
Route::get('/faq', FaqIndexController::class)->name('api.faq.index');
Route::get('/gallery', GalleryArtworkController::class)->name('api.gallery.index');
Route::get('/redirects/resolve', RedirectResolveController::class)->name('api.redirects.resolve');

// ---------------------------------------------------------------------------
// Analytics ingestion — 60 per minute per IP
// ---------------------------------------------------------------------------
Route::middleware('throttle:60,1')->group(function (): void {
    Route::post('/analytics/events', AnalyticsEventIngestController::class)->name('api.analytics.events.store');
});

// ---------------------------------------------------------------------------
// Auth — strict rate-limit: 5 attempts per minute per IP
// ---------------------------------------------------------------------------
Route::middleware('throttle:5,1')->group(function (): void {
    Route::post('/auth/login', CustomerLoginController::class)->name('api.auth.login');
    Route::post('/auth/forgot-password', CustomerForgotPasswordController::class)->name('api.auth.forgot-password');
});

// Registration, verification & password reset — 10 per minute per IP
Route::middleware('throttle:10,1')->group(function (): void {
    Route::post('/auth/register', CustomerRegisterController::class)->name('api.auth.register');
    Route::post('/auth/reset-password', CustomerResetPasswordController::class)->name('api.auth.reset-password');
    Route::get('/auth/email/verify/{id}/{hash}', EmailVerificationController::class)
        ->middleware(['signed'])
        ->name('verification.verify');
});

use App\Http\Controllers\Api\CouponValidateController;

Route::middleware('throttle:20,1')->group(function (): void {
    Route::post('/inquiries', ContactInquiryController::class)->name('api.inquiries.store');
    Route::post('/quote', QuoteController::class)->name('api.quote');
    Route::post('/coupons/validate', CouponValidateController::class)->name('api.coupons.validate');
    Route::post('/catalog/products/{slug}/reviews', [ProductReviewController::class, 'store'])->name('api.catalog.products.reviews.store');
    Route::post('/returns', [OrderReturnController::class, 'store'])->name('api.returns.store');
    Route::post('/cookie-consents', CookieConsentController::class)->name('api.cookie-consents.store');
    Route::post('/catalog/products/back-in-stock-subscribe', BackInStockSubscribeController::class)->name('api.catalog.products.back-in-stock-subscribe');
    Route::post('/questionnaire-submissions', QuestionnaireSubmissionController::class)->name('api.questionnaire-submissions.store');
});

// ---------------------------------------------------------------------------
// Cart — 60 per minute per IP
// ---------------------------------------------------------------------------
Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('/cart', [CartController::class, 'show'])->name('api.cart.show');
    Route::post('/cart/items', [CartController::class, 'store'])->name('api.cart.items.store');
    Route::put('/cart/items/{itemId}', [CartController::class, 'update'])->name('api.cart.items.update');
    Route::delete('/cart/items/{itemId}', [CartController::class, 'destroy'])->name('api.cart.items.destroy');
});

// ---------------------------------------------------------------------------
// Checkout — 30 per minute per IP
// ---------------------------------------------------------------------------
Route::middleware('throttle:30,1')->group(function (): void {
    Route::post('/checkout/draft', CheckoutDraftController::class)->name('api.checkout.draft');
    Route::post('/checkout/place', CheckoutPlaceController::class)->name('api.checkout.place');
    Route::get('/checkout/orders/{number}', CheckoutOrderShowController::class)->name('api.checkout.orders.show');
    Route::post('/checkout/orders/{number}/payment-session', CheckoutPaymentSessionController::class)->name('api.checkout.orders.payment-session');
    
    // B2B GUS BIR / MF White List search
    Route::get('/b2b/gus/{nip}', B2bGusController::class)->name('api.b2b.gus');
});

// ---------------------------------------------------------------------------
// Payment webhooks â€” no throttle (handled by provider signature verification)
// ---------------------------------------------------------------------------
Route::post('/integrations/stripe/payment-callback', \App\Http\Controllers\Api\StripePaymentCallbackController::class)->name('api.integrations.stripe.payment-callback');
Route::post('/integrations/przelewy24/payment-callback', Przelewy24PaymentCallbackController::class)->name('api.integrations.przelewy24.payment-callback');
Route::match(['get', 'post'], '/integrations/baselinker/shipment-callback', 'App\\Http\\Controllers\\Api\\BaseLinkerShipmentCallbackController')->name('api.integrations.baselinker.shipment-callback');

// ---------------------------------------------------------------------------
// Authenticated routes
// ---------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', CustomerLogoutController::class)->name('api.auth.logout');
    
    // Resend verification link â€” throttled to prevent spam
    Route::middleware('throttle:5,1')
        ->post('/auth/email/resend', EmailVerificationResendController::class)
        ->name('verification.resend');

    // Email-verified only routes
    Route::middleware('verified')->group(function (): void {
        Route::get('/account/me', AccountMeController::class)->name('api.account.me');
        Route::get('/account/orders', AccountOrdersController::class)->name('api.account.orders');
        
        // Customer Address Book
        Route::apiResource('/account/addresses', CustomerAddressController::class);

        // Wishlist
        Route::get('/account/wishlist', [WishlistController::class, 'index'])->name('api.account.wishlist.index');
        Route::post('/account/wishlist', [WishlistController::class, 'store'])->name('api.account.wishlist.store');
        Route::delete('/account/wishlist/{productId}', [WishlistController::class, 'destroy'])->name('api.account.wishlist.destroy');

        // Returns (RMA)
        Route::get('/account/returns', [OrderReturnController::class, 'index'])->name('api.account.returns.index');
        Route::get('/account/returns/{orderReturn}', [OrderReturnController::class, 'show'])->name('api.account.returns.show');
    });
});

