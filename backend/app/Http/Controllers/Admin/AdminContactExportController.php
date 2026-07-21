<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminContactExportController extends Controller
{
    public function customers(): StreamedResponse
    {
        $this->ensureAdmin();

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if (! $handle) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['name', 'email', 'segment', 'phone', 'orders_count', 'marketing_consent_at', 'last_order_at'], ';');

            User::query()
                ->where('is_admin', false)
                ->with('customerProfile')
                ->withCount('orders')
                ->orderBy('email')
                ->chunk(200, function ($users) use ($handle): void {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->name,
                            $user->email,
                            $user->customerProfile?->segment?->value ?? 'regular',
                            $user->customerProfile?->phone,
                            $user->orders_count,
                            optional($user->customerProfile?->marketing_consent_at)->toIso8601String(),
                            optional($user->customerProfile?->last_order_at)->toIso8601String(),
                        ], ';');
                    }
                });

            fclose($handle);
        }, 'customers-export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }
}