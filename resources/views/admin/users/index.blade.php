@extends('layouts.app')

@section('title', 'Users')
@section('heading', 'Users')

@section('actions')
    <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-700">
        + Add user
    </a>
@endsection

@section('content')
    <form method="GET" class="mb-4 flex gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, email, company…"
               class="w-72 rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
        <button class="rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">Search</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase tracking-wide text-neutral-500">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Bot</th>
                    <th class="px-4 py-3">Subscription</th>
                    <th class="px-4 py-3 text-right">Clients</th>
                    <th class="px-4 py-3 text-right">Payments</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($users as $user)
                    <tr class="hover:bg-neutral-50">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-xs text-neutral-500">{{ $user->email }}{{ $user->company ? ' · '.$user->company : '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->is_paid)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Paid</span>
                            @else
                                <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-700">Not paid</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->bot_active)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Active</span>
                            @else
                                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-neutral-500">
                            {{ $user->subscription_plan ? ucfirst($user->subscription_plan) : '—' }}
                            @if ($user->subscription_ends_at)
                                <div>ends {{ $user->subscription_ends_at->format('M j, Y') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ $user->clients_count }}</td>
                        <td class="px-4 py-3 text-right">{{ $user->paid_payments_count }}x</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-xs font-medium text-neutral-500 hover:text-neutral-900">Manage →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-neutral-400">No users yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
