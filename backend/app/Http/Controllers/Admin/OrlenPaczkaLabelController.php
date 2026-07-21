<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrlenPaczkaLabelController extends Controller
{
    /**
     * Download the local PDF label.
     */
    public function download(string $number): StreamedResponse
    {
        $order = Order::query()
            ->where('number', $number)
            ->whereNotNull('tracking_number')
            ->firstOrFail();

        $path = "orlen/labels/{$order->tracking_number}.pdf";

        if (!Storage::exists($path)) {
            abort(404, 'Etykieta nie została odnaleziona w lokalnej pamięci.');
        }

        return Storage::download($path, "etykieta_orlen_{$order->number}_{$order->tracking_number}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="etykieta_' . $order->tracking_number . '.pdf"',
        ]);
    }
}
