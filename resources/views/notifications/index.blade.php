<x-layout :title="'Notifications'">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="page-kicker">Personal inbox</div>
            <h1 class="page-title">Notifications</h1>
            <p class="page-subtitle">Review compliance alerts, expiry reminders, workflow updates, and actions assigned to you.</p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn-secondary" type="submit">Mark all {{ $unreadCount }} as read</button>
            </form>
        @endif
    </div>

    <form method="GET" class="filter-bar">
        <div>
            <label class="field-label" for="type">Alert type</label>
            <select class="field-control" id="type" name="type">
                <option value="">All types</option>
                @foreach (['info', 'success', 'warning', 'urgent', 'action_required'] as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
        </div>
        <label class="stat-chip mb-0.5 cursor-pointer">
            <input type="checkbox" name="unread" value="1" class="rounded border-slate-300" @checked(request()->boolean('unread'))>
            Unread only
        </label>
        <button class="btn-primary btn-xs" type="submit">Apply filters</button>
        <a class="btn-secondary btn-xs" href="{{ route('notifications.index') }}">Clear</a>
    </form>

    <div class="panel overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse ($notifications as $notification)
                @php
                    $tone = match($notification->type) {
                        'urgent' => 'bg-red-50 text-red-700',
                        'warning', 'action_required' => 'bg-amber-50 text-amber-700',
                        'success' => 'bg-emerald-50 text-emerald-700',
                        default => 'bg-indigo-50 text-indigo-700',
                    };
                @endphp
                <article class="flex gap-4 px-5 py-5 {{ $notification->is_read ? 'bg-white' : 'bg-indigo-50/30' }}">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $tone }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0h6z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-semibold text-slate-900">{{ $notification->title }}</h2>
                                    @unless ($notification->is_read)<span class="h-2 w-2 rounded-full bg-indigo-500"></span>@endunless
                                </div>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $notification->message }}</p>
                            </div>
                            <time class="whitespace-nowrap text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</time>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span class="badge-neutral">{{ ucwords(str_replace('_', ' ', $notification->type)) }}</span>
                            @if ($notification->vendor)<span>{{ $notification->vendor->name }}</span>@endif
                            @if ($notification->vendorDocument)<span>{{ $notification->vendorDocument->documentType?->name }}</span>@endif
                        </div>
                    </div>
                    @unless ($notification->is_read)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}" class="self-center">
                            @csrf
                            <button type="submit" class="btn-soft btn-xs">Open</button>
                        </form>
                    @endunless
                </article>
            @empty
                <div class="empty-state">
                    <div class="empty-state-icon"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M5 13l4 4L19 7"/></svg></div>
                    <h2 class="font-semibold text-slate-900">You are all caught up</h2>
                    <p class="mt-1 text-sm text-slate-500">No notifications match the selected filters.</p>
                </div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $notifications->links() }}</div>
</x-layout>
