<?php

namespace App\Services;

use App\Mail\UserCredentialsMail;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Payment + subscription lifecycle (per-client pricing):
 *  - each client has their own price/interval + assigned merchant account
 *  - create payment links from the client's own pricing
 *  - mark paid -> activate bot + extend subscription by the client's interval
 *  - expire subscriptions (scheduled) -> disable bot + reset status
 */
class SubscriptionService
{
    /**
     * Create a payment link using the client's OWN pricing and their
     * assigned merchant account (falls back to the platform-active one).
     */
    public function createPaymentLink(User $user): Payment
    {
        $gateway = $user->preferredGateway();

        return Payment::query()->create([
            'user_id' => $user->id,
            'gateway_account_id' => $gateway?->id,
            'label' => $user->plan_label ?: 'Subscription',
            'amount' => $user->plan_amount ?? 0,
            'currency' => $user->plan_currency ?: 'USD',
            'status' => Payment::STATUS_PENDING,
            'reference' => Payment::generateReference(),
            'gateway' => $gateway?->gateway,
            'mode' => $gateway?->mode ?? 'sandbox',
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Mark a payment as paid and extend the client's subscription by
     * their own billing interval (plan_days).
     */
    public function markPaid(Payment $payment, ?string $gatewayReference = null): void
    {
        if ($payment->isPaid()) {
            return;
        }

        DB::transaction(function () use ($payment, $gatewayReference): void {
            $payment->update([
                'status' => Payment::STATUS_PAID,
                'paid_at' => now(),
                'gateway' => $payment->gateway ?: 'manual',
                'gateway_reference' => $gatewayReference,
            ]);

            $user = $payment->user()->lockForUpdate()->firstOrFail();

            $base = $user->subscription_ends_at?->isFuture() ? $user->subscription_ends_at : now();
            $days = $user->plan_days ?? 30;

            $user->update([
                'is_paid' => true,
                'paid_at' => now(),
                'subscription_plan' => $user->plan_label ?: 'custom',
                'subscription_days' => $days,
                'subscription_ends_at' => $base->copy()->addDays($days),
                'bot_active' => true,
                'payment_reminder_sent' => false,
            ]);
        });
    }

    /**
     * Expire users whose subscription ran out:
     * bot disabled + status back to unpaid.
     *
     * @return int number of expired users
     */
    public function expireOverdueSubscriptions(): int
    {
        return User::query()
            ->where('role', User::ROLE_USER)
            ->where('is_paid', true)
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<=', now())
            ->update([
                'is_paid' => false,
                'bot_active' => false,
                'payment_reminder_sent' => false,
            ]);
    }

    public function sendCredentials(User $user, ?Payment $payment = null): void
    {
        Mail::to($user->email)->send(new UserCredentialsMail($user, $payment));
    }

    /**
     * Send credentials including a freshly generated plain password
     * (only available at creation time).
     */
    public function sendCredentialsWithPassword(User $user, string $plainPassword, ?Payment $payment = null): void
    {
        Mail::to($user->email)->send(new UserCredentialsMail($user, $payment, $plainPassword));
    }

    /**
     * The default sandbox/demo interval options offered in forms.
     *
     * @return array<string, int>
     */
    public static function intervalPresets(): array
    {
        return [
            'Weekly' => 7,
            'Bi-weekly' => 14,
            'Monthly' => 30,
            'Quarterly' => 90,
            'Yearly' => 365,
        ];
    }
}
