<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $totalUsers = User::query()->where('role', User::ROLE_USER)->count();
        $paidUsers = User::query()->where('role', User::ROLE_USER)->where('is_paid', true)->count();
        $activeBots = User::query()->where('bot_active', true)->count();
        $totalClients = Client::query()->count();
        $unreadClients = Client::query()->where('is_read', false)->count();

        $revenueMonth = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $revenueWeek = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', now()->startOfWeek())
            ->sum('amount');

        $revenueAll = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');

        // Revenue per day for the last 30 days (chart)
        $revenueByDay = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->where('paid_at', '>=', now()->subDays(30))
            ->select(DB::raw('date(paid_at) as day'), DB::raw('sum(amount) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $recentClients = Client::query()
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        $topBuyers = User::query()
            ->where('role', User::ROLE_USER)
            ->withCount(['payments as paid_payments_count' => fn ($query) => $query->where('status', Payment::STATUS_PAID)])
            ->withSum(['payments as paid_total' => fn ($query) => $query->where('status', Payment::STATUS_PAID)], 'amount')
            ->orderByDesc('paid_total')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'paid_users' => $paidUsers,
                'active_bots' => $activeBots,
                'total_clients' => $totalClients,
                'unread_clients' => $unreadClients,
                'revenue_month' => $revenueMonth,
                'revenue_week' => $revenueWeek,
                'revenue_all' => $revenueAll,
            ],
            'revenueByDay' => $revenueByDay,
            'recentClients' => $recentClients,
            'topBuyers' => $topBuyers,
        ]);
    }
}
