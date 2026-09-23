<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactInquiryAdminMail;
use App\Models\ContactInquiry;
use App\Models\TransactionalEmailLog;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ContactInquiryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $allFields = $request->all();

        $attachments = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('attachments', 'public');
                    $attachments[] = asset('storage/' . $path);
                }
            }
        }

        // Wyciągamy dynamiczne dodatkowe pola (np. z briefu/wielokrokowego formularza)
        $payload = array_diff_key($allFields, array_flip(['name', 'email', 'phone', 'subject', 'message', 'files']));

        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        $sanitize = function ($value) use (&$sanitize) {
            if (is_array($value)) {
                return array_map($sanitize, $value);
            }
            return is_string($value) ? strip_tags($value) : $value;
        };

        $inquiry = ContactInquiry::create([
            'name' => strip_tags($data['name']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => isset($data['subject']) ? strip_tags($data['subject']) : null,
            'message' => strip_tags($data['message']),
            'payload' => !empty($payload) ? array_map($sanitize, $payload) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'new',
        ]);

        // Wysyłka powiadomienia e-mail do administratora / artysty
        $recipient = null;
        try {
            /** @var StoreSettings $settings */
            $settings = app(StoreSettings::class);
            $recipient = $settings->adminNotificationEmail()
                ?: config('mail.from.address')
                ?: 'kontakt@hellokostek.pl';

            if (filled($recipient)) {
                Mail::to($recipient)->send(new ContactInquiryAdminMail($inquiry));

                TransactionalEmailLog::create([
                    'email_type' => 'contact_inquiry_admin',
                    'recipient' => $recipient,
                    'subject' => 'Nowe zapytanie o wycenę portretu: ' . $inquiry->name,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'payload' => [
                        'inquiry_id' => $inquiry->id,
                        'customer_email' => $inquiry->email,
                        'customer_name' => $inquiry->name,
                    ],
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Nie udało się wysłać powiadomienia e-mail o zapytaniu: ' . $e->getMessage());

            TransactionalEmailLog::create([
                'email_type' => 'contact_inquiry_admin',
                'recipient' => $recipient ?? 'unknown',
                'subject' => 'Nowe zapytanie o wycenę portretu: ' . $inquiry->name,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'payload' => [
                    'inquiry_id' => $inquiry->id,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $inquiry->id,
                'status' => $inquiry->status,
            ]
        ], 201);
    }
}

