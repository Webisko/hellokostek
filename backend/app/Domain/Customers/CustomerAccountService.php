<?php

namespace App\Domain\Customers;

use App\Domain\Commerce\Enums\CustomerSegment;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerAccountService
{
    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->with('customerProfile')
            ->whereRaw('lower(email) = ?', [mb_strtolower($email)])
            ->first();
    }

    public function register(array $validated): User
    {
        /** @var User $user */
        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? '')),
                'email' => mb_strtolower($validated['email']),
                'password' => $validated['password'],
            ]);

            CustomerProfile::query()->create([
                'user_id' => $user->id,
                'segment' => CustomerSegment::Regular,
                'phone' => $validated['phone'] ?? null,
                'marketing_consent_at' => ! empty($validated['marketing_consent']) ? now() : null,
                'metadata' => [
                    'source' => 'api.auth.register',
                ],
            ]);

            $this->linkOrdersToUser($user);
            $this->syncProfile($user->load('customerProfile'));

            return $user->load('customerProfile');
        });

        return $user;
    }

    public function linkOrdersToUser(User $user): void
    {
        Order::query()
            ->whereNull('user_id')
            ->whereRaw('lower(customer_email) = ?', [mb_strtolower($user->email)])
            ->update([
                'user_id' => $user->id,
            ]);
    }

    public function syncProfile(User $user): CustomerProfile
    {
        $profile = $user->customerProfile ?? CustomerProfile::query()->create([
            'user_id' => $user->id,
            'segment' => CustomerSegment::Regular,
        ]);

        $completedOrdersCount = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $lastOrderAt = Order::query()
            ->where('user_id', $user->id)
            ->whereNotNull('placed_at')
            ->max('placed_at');

        $profile->forceFill([
            'completed_orders_count' => $completedOrdersCount,
            'last_order_at' => $lastOrderAt,
            'segment' => $this->resolveSegment($profile, $completedOrdersCount),
        ])->save();

        return $profile->refresh();
    }

    private function resolveSegment(CustomerProfile $profile, int $completedOrdersCount): CustomerSegment
    {
        if ($profile->segment === CustomerSegment::WholesaleThirty) {
            return CustomerSegment::WholesaleThirty;
        }

        return match (true) {
            $completedOrdersCount >= 6 => CustomerSegment::LoyalEight,
            $completedOrdersCount >= 3 => CustomerSegment::LoyalFive,
            default => CustomerSegment::Regular,
        };
    }
}