<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->with(['user', 'gatewayAccount'])
            ->latest()
            ->paginate(20);

        // All figures are derived from the payments table, so deleting a
        // row (e.g. sandbox test data) automatically adjusts the totals.
        $paidQuery = Payment::query()->where('status', Payment::STATUS_PAID);

        return view('admin.payments.index', [
            'payments' => $payments,
            'totals' => [
                'paid' => (clone $paidQuery)->sum('amount'),
                'pending' => Payment::query()->where('status', Payment::STATUS_PENDING)->sum('amount'),
                'count_paid' => (clone $paidQuery)->count(),
                'count_pending' => Payment::query()->where('status', Payment::STATUS_PENDING)->count(),
                'live' => (clone $paidQuery)->where('mode', 'live')->sum('amount'),
                'sandbox' => (clone $paidQuery)->where('mode', 'sandbox')->sum('amount'),
                'month' => (clone $paidQuery)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'week' => (clone $paidQuery)->where('paid_at', '>=', now()->startOfWeek())->sum('amount'),
            ],
            'byGateway' => (clone $paidQuery)
                ->select('gateway', DB::raw('count(*) as total_count'), DB::raw('sum(amount) as total_amount'))
                ->groupBy('gateway')
                ->get(),
            'ordersPaid' => Order::query()->where('payment_status', 'paid')->count(),
            'ordersPaidTotal' => Order::query()->where('payment_status', 'paid')->sum('total'),
        ]);
    }

    /**
     * Delete a payment record (used to clean up sandbox test data).
     * All revenue analytics recalculate automatically.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return back()->with('status', 'Payment deleted — analytics recalculated.');
    }
}
