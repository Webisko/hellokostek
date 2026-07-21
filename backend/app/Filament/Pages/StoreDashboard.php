<?php

namespace App\Filament\Pages;

use App\Models\BlogPost;
use App\Models\ContactInquiry;
use App\Models\ContentPage;
use App\Models\CustomerProfile;
use App\Models\FailedJob;
use App\Models\IntegrationLog;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductReview;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class StoreDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Kokpit';

    protected static ?string $title = 'Kokpit';

    protected static ?int $navigationSort = -2;

    protected static ?string $slug = 'kokpit';

    protected string $view = 'filament.pages.store-dashboard';

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    /**
     * @return array<int, array{label: string, value: string, context: string, tone: string, url: string}>
     */
    public function heroStats(): array
    {
        $revenueToday = (int) Order::query()
            ->whereDate('placed_at', today())
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
            
        $ordersToday = Order::query()
            ->whereDate('placed_at', today())
            ->count();

        $inquiries30Days = ContactInquiry::query()
            ->whereDate('created_at', '>=', now()->subDays(30)->toDateString())
            ->count();

        $activeProducts = Product::query()
            ->where('is_active', true)
            ->count();

        return [
            [
                'label' => 'Dzisiejszy przychód',
                'value' => number_format($revenueToday / 100, 2, ',', ' ') . ' PLN',
                'context' => 'Suma opłaconych zamówień ze sklepu',
                'tone' => 'success',
                'url' => '/admin/zamowienia',
            ],
            [
                'label' => 'Dzisiejsze zamówienia',
                'value' => number_format($ordersToday, 0, ',', ' '),
                'context' => 'Gotowe prace kupione dzisiaj',
                'tone' => 'info',
                'url' => '/admin/zamowienia',
            ],
            [
                'label' => 'Zapytania o portrety',
                'value' => number_format($inquiries30Days, 0, ',', ' '),
                'context' => 'Zgłoszenia z ostatnich 30 dni',
                'tone' => 'warning',
                'url' => '/admin/zapytania-kontaktowe',
            ],
            [
                'label' => 'Oferta w sklepie',
                'value' => number_format($activeProducts, 0, ',', ' '),
                'context' => 'Aktywne obrazy i prace',
                'tone' => 'default',
                'url' => '/admin/produkty',
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, description: string, tone: string, url: string}>
     */
    public function operationalStats(): array
    {
        $pendingFulfillment = Order::query()
            ->where('status', 'placed')
            ->where('fulfillment_status', 'pending')
            ->count();

        $newInquiries = ContactInquiry::query()
            ->where('status', 'new')
            ->count();

        $pendingReturns = OrderReturn::query()
            ->where('status', 'pending')
            ->count();

        return [
            [
                'label' => 'Nowe zapytania o portret',
                'value' => number_format($newInquiries, 0, ',', ' '),
                'description' => 'Nowe wiadomości z formularza zamówień portretu.',
                'tone' => $newInquiries > 0 ? 'danger' : 'success',
                'url' => '/admin/zapytania-kontaktowe',
            ],

            [
                'label' => 'Zamówienia do wysyłki',
                'value' => number_format($pendingFulfillment, 0, ',', ' '),
                'description' => 'Zamówienia opłacone czekające na pakowanie i nadanie.',
                'tone' => $pendingFulfillment > 0 ? 'warning' : 'success',
                'url' => '/admin/zamowienia',
            ],

            [
                'label' => 'Zwroty do rozpatrzenia',
                'value' => number_format($pendingReturns, 0, ',', ' '),
                'description' => 'Zgłoszenia zwrotów od klientów do weryfikacji.',
                'tone' => $pendingReturns > 0 ? 'warning' : 'success',
                'url' => '/admin/zwroty',
            ],
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Order>
     */
    public function recentOrders(): \Illuminate\Database\Eloquent\Collection
    {
        return Order::query()
            ->latest('placed_at')
            ->limit(5)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ContactInquiry>
     */
    public function recentInquiries(): \Illuminate\Database\Eloquent\Collection
    {
        return ContactInquiry::query()
            ->latest('created_at')
            ->limit(5)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ProductReview>
     */
    public function recentReviews(): \Illuminate\Database\Eloquent\Collection
    {
        return ProductReview::query()
            ->with('product')
            ->latest('created_at')
            ->limit(5)
            ->get();
    }
}
