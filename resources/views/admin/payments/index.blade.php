@extends('layouts.app')

@section('title', 'Payments')
@section('heading', 'Payments & revenue')

@section('content')
    {{-- Revenue analytics --}}
    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Total collected">${{ number_format($totals['paid'], 2) }}</x-stat-card>
        <x-stat-card label="This month">${{ number_format($totals['month'], 2) }}</x-stat-card>
        <x-stat-card label="This week">${{ number_format($totals['week'], 2) }}</x-stat-card>
        <x-stat-card label="Pending">${{ number_format($totals['pending'], 2) }}</x-stat-card>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Paid transactions">{{ $totals['count_paid'] }}</x-stat-card>
        <x-stat-card label="Live revenue">${{ number_format($totals['live'], 2) }}</x-stat-card>
        <x-stat-card label="Sandbox revenue">${{ number_format($totals['sandbox'], 2) }}</x-stat-card>
        <x-stat-card label="Invoice payments">${{ number_format($ordersPaidTotal, 2) }}
            <div class="text-xs font-normal text-neutral-500">{{ $ordersPaid }} paid invoices</div>
        </x-stat-card>
    </div>

    @if ($byGateway->isNotEmpty())
        <div class="mb-6 flex flex-wrap gap-3">
            @foreach ($byGateway as $row)
                <div class="rounded-lg border border-neutral-200 bg-white px-4 py-2 text-sm">
                    <span class="font-semibold capitalize">{{ $row->gateway ?: 'manual' }}</span>
                    <span class="ml-2 text-neutral-500">${{ number_format($row->total_amount, 2) }} · {{ $row->total_count }}x</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
        <div class="flex items-center justify-between border-b border-neutral-100 px-4 py-3">
            <h2 class="text-sm font-semibold">All transactions</h2>
            <span class="text-xs text-neutral-400">Delete se analytics automatically recalculate hoti hai (sandbox cleanup ke liye)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase tracking-wide text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Plan / Source</th>
                        <th class="px-4 py-3">Gateway</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($payments as $payment)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.users.show', $payment->user) }}" class="font-medium hover:underline">{{ $payment->user->name }}</a>
                                <div class="text-xs text-neutral-500">{{ $payment->user->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $payment->label ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="capitalize">{{ $payment->gateway ?: 'manual' }}</span>
                                <span class="ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $payment->mode === 'live' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $payment->mode }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                @if ($payment->isPaid())
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Paid</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-neutral-500">{{ ($payment->paid_at ?? $payment->created_at)->format('M j, Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.payments.destroy', $payment) }}"
                                      onsubmit="return confirm('Delete this transaction? Analytics will recalculate.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-neutral-400">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
