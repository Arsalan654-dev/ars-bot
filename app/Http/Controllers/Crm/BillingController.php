<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /**
     * Shown when an unpaid user tries to log in / access CRM.
     */
    public function paymentRequired(Request $request): View
    {
        $user = $request->user();

        $payment = $user->payments()->where('status', Payment::STATUS_PENDING)->latest()->first();

        if ($payment === null) {
            $payment = $this->subscriptions->createPaymentLink($user);
        }

        return view('crm.payment-required', [
            'user' => $user,
            'payment' => $payment,
        ]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('crm.billing.index', [
            'user' => $user,
            'payments' => $user->payments()->with('gatewayAccount')->latest()->get(),
            'pendingPayment' => $user->payments()->where('status', Payment::STATUS_PENDING)->latest()->first(),
        ]);
    }

    /**
     * Renew/extend the subscription using the client's own pricing.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Re-use an existing pending link if present, otherwise create one.
        $payment = $user->payments()->where('status', Payment::STATUS_PENDING)->latest()->first()
            ?? $this->subscriptions->createPaymentLink($user);

        return redirect()->route('payment.show', $payment->reference);
    }
}
