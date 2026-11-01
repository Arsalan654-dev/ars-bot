<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks CRM access for users whose payment/subscription is not active.
 * They are redirected to the payment page with an explanatory message.
 */
class EnsureSubscriptionActive
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // The customer CRM is for paying customers; the super admin
        // manages their own bot from the admin panel instead.
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasActiveSubscription()) {
            return $next($request);
        }

        $payment = $user->payments()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($payment === null) {
            $payment = $this->subscriptions->createPaymentLink($user);
        }

        return redirect()
            ->route('payment.show', $payment->reference)
            ->with('error', 'Please complete your payment first to access your bot CRM.');
    }
}
