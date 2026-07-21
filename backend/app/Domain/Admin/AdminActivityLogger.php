<?php

namespace App\Domain\Admin;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function log(Model $subject, string $event, string $summary, ?array $oldValues = null, ?array $newValues = null, array $metadata = []): AdminActivityLog
    {
        $oldValues = $this->maskSensitiveData($oldValues);
        $newValues = $this->maskSensitiveData($newValues);

        return AdminActivityLog::query()->create([
            'actor_id' => auth()->id(),
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'event' => $event,
            'summary' => $summary,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array<string, mixed>|null
     */
    private function maskSensitiveData(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sensitiveKeys = [
            'email', 'customer_email', 'billing_email',
            'phone', 'customer_phone', 'phone_number', 'billing_phone',
            'password', 'password_hash', 'remember_token',
            'address', 'billing_address', 'shipping_address', 'street', 'city', 'zip', 'postal_code',
            'first_name', 'last_name', 'company_name', 'nip', 'tax_number'
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '[MASKOWANE]';
            }
        }

        return $data;
    }
}