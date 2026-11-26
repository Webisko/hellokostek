<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InPostLabelController extends Controller
{
    /**
     * Download the local PDF label.
     */
    public function download(string $number): StreamedResponse
    {
        abort_unless(auth()->user()?->isStaff(), 403);

        $order = Order::query()
            ->where('number', $number)
            ->whereNotNull('tracking_number')
            ->firstOrFail();

        $tracking = basename((string) $order->tracking_number);
        abort_if($tracking === '', 404, 'Nieprawidłowy numer przesyłki.');

        $path = "inpost/labels/{$tracking}.pdf";

        if (!Storage::exists($path)) {
            abort(404, 'Etykieta nie została odnaleziona w lokalnej pamięci.');
        }

        return Storage::download($path, "etykieta_{$order->number}_{$tracking}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="etykieta_' . $tracking . '.pdf"',
        ]);
    }
}
