<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactInquiryAttachmentController extends Controller
{
    public function show(ContactInquiry $inquiry, int $index): StreamedResponse
    {
        abort_unless(auth()->user()?->isStaff(), 403);

        $rawAttachments = $inquiry->payload['attachments'] ?? [];
        if (! is_array($rawAttachments)) {
            abort(404);
        }

        $attachments = array_values($rawAttachments);
        if (! array_key_exists($index, $attachments)) {
            abort(404);
        }

        $rawPath = (string) $attachments[$index];

        // Support legacy attachments stored on public disk prior to hardening
        if (str_starts_with($rawPath, 'http') || str_contains($rawPath, '/storage/attachments/')) {
            $urlPath = (string) parse_url($rawPath, PHP_URL_PATH);
            $filename = basename($urlPath);
            $legacyPath = 'attachments/' . $filename;

            if ($filename !== '' && Storage::disk('public')->exists($legacyPath)) {
                return Storage::disk('public')->response($legacyPath, $filename, [
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }

            abort(404);
        }

        $safeRelative = ltrim(str_replace(['..', '\\'], ['', '/'], $rawPath), '/');
        if (! str_starts_with($safeRelative, 'contact-inquiries/')) {
            abort(404);
        }

        $scopedPath = 'contact-inquiries/' . basename($safeRelative);
        if ($scopedPath === 'contact-inquiries/' || ! Storage::disk('local')->exists($scopedPath)) {
            abort(404);
        }

        return Storage::disk('local')->response($scopedPath, basename($scopedPath), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
