@extends('layouts.app')

@section('title', 'Knowledge base')
@section('heading', 'Knowledge base')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse ($entries as $entry)
                <form method="POST" action="{{ route('crm.knowledge.update', $entry) }}" class="rounded-xl border border-neutral-200 bg-white p-5">
                    @csrf
                    @method('PUT')
                    <div class="flex items-start gap-4">
                        <div class="flex-1 space-y-3">
                            <input name="title" value="{{ $entry->title }}" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium">
                            <textarea name="content" rows="4" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">{{ $entry->content }}</textarea>
                            <div class="flex items-center gap-4">
                                <select name="type" class="rounded-lg border border-neutral-300 px-3 py-1.5 text-xs">
                                    @foreach (['text' => 'General', 'faq' => 'FAQ', 'product' => 'Product', 'service' => 'Service', 'policy' => 'Policy'] as $value => $label)
                                        <option value="{{ $value }}" @selected($entry->type === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked($entry->is_active) class="rounded border-neutral-300">
                                    Active
                                </label>
                                <button class="rounded-lg bg-neutral-900 px-4 py-1.5 text-xs font-semibold text-white hover:bg-neutral-700">Save</button>
                            </div>
                        </div>
                    </div>
                </form>
                <form method="POST" action="{{ route('crm.knowledge.destroy', $entry) }}" onsubmit="return confirm('Delete this entry?')" class="-mt-2">
                    @csrf
                    @method('DELETE')
                    <button class="text-xs font-medium text-red-500 hover:text-red-700">Delete entry</button>
                </form>
            @empty
                <div class="rounded-xl border border-neutral-200 bg-white p-10 text-center text-sm text-neutral-400">
                    Your knowledge base is empty. Add products, services, FAQs or policies so your bot
                    can answer like an expert.
                </div>
            @endforelse
        </div>

        <div>
            <form method="POST" action="{{ route('crm.knowledge.store') }}" class="rounded-xl border border-neutral-200 bg-white p-5">
                @csrf
                <h3 class="mb-4 text-sm font-semibold">Add entry</h3>
                <div class="space-y-3">
                    <input name="title" placeholder="e.g. Pricing — Monthly plan" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <textarea name="content" rows="5" placeholder="Full details the bot should know…" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"></textarea>
                    <select name="type" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                        <option value="text">General</option>
                        <option value="faq">FAQ</option>
                        <option value="product">Product</option>
                        <option value="service">Service</option>
                        <option value="policy">Policy</option>
                    </select>
                    <input type="hidden" name="is_active" value="1">
                    <button class="w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">Add to knowledge base</button>
                </div>
            </form>
        </div>
    </div>
@endsection
