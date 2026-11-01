<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'clients_total' => $user->clients()->count(),
            'clients_unread' => $user->clients()->where('is_read', false)->count(),
            'orders_confirmed' => $user->clients()->where('order_confirmed', true)->count(),
            'orders_total' => $user->orders()->sum('total'),
            'conversations_7d' => $user->conversations()
                ->where('last_activity_at', '>=', now()->subDays(7))
                ->count(),
        ];

        $clientsByDay = $user->clients()
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $recentClients = $user->clients()->latest()->limit(8)->get();

        $recentConversations = $user->conversations()
            ->with('client')
            ->latest('last_activity_at')
            ->limit(8)
            ->get();

        return view('crm.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'clientsByDay' => $clientsByDay,
            'recentClients' => $recentClients,
            'recentConversations' => $recentConversations,
        ]);
    }
}
