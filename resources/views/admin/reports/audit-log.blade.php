<x-layout :title="'Audit Trail'">
    @php
        $isAuditorOnly = auth()->user()->isAuditor();
        $indexRoute = $isAuditorOnly ? route('auditor.dashboard') : route('admin.reports.index');
        $clearRoute = $isAuditorOnly ? route('auditor.audit-log') : route('admin.reports.audit-log');
        $exportRoute = $isAuditorOnly ? route('auditor.audit-log.export', request()->query()) : route('admin.reports.audit-log.export', request()->query());
    @endphp
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div><div class="mb-2 text-xs font-medium text-slate-500"><a href="{{ $indexRoute }}" class="hover:text-indigo-700">{{ $isAuditorOnly ? 'Assurance view' : 'Reports' }}</a> / Audit trail</div><div class="page-kicker">Immutable evidence</div><h1 class="page-title">Audit trail</h1><p class="page-subtitle">Search system activity by vendor, event type, actor, and period. Audit entries preserve snapshots even when source records change.</p></div>
        <a href="{{ $exportRoute }}" class="btn-primary"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>Export filtered CSV</a>
    </div>

    <form method="GET" class="filter-bar">
        <div class="min-w-[220px]"><label class="field-label">Vendor</label><select name="vendor_id" class="field-control w-full"><option value="">All vendors</option>@foreach ($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->name }}</option>@endforeach</select></div>
        <div><label class="field-label">Event type</label><select name="event_type" class="field-control"><option value="">All events</option>@foreach ($eventTypes as $type)<option value="{{ $type }}" @selected(request('event_type') === $type)>{{ ucwords(str_replace('_',' ',$type)) }}</option>@endforeach</select></div>
        <div><label class="field-label">From</label><input type="date" name="from_date" value="{{ request('from_date') }}" class="field-control"></div>
        <div><label class="field-label">To</label><input type="date" name="to_date" value="{{ request('to_date') }}" class="field-control"></div>
        <div class="flex gap-2"><button class="btn-primary btn-xs">Apply</button><a href="{{ $clearRoute }}" class="btn-secondary btn-xs">Reset</a></div>
    </form>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Timestamp</th><th>Actor</th><th>Vendor</th><th>Event</th><th>Description</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap"><div class="font-medium text-slate-700">{{ $log->occurred_at->format('d M Y') }}</div><div class="text-xs text-slate-400">{{ $log->occurred_at->format('H:i:s') }}</div></td>
                        <td><div class="flex items-center gap-3"><div class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600">{{ strtoupper(substr($log->actor_name ?? 'S', 0, 1)) }}</div><div><div class="text-sm font-medium text-slate-800">{{ $log->actor_name ?? 'System' }}</div><div class="text-xs text-slate-400">{{ $log->actor_role ? ucwords(str_replace('_',' ',$log->actor_role)) : 'Automated process' }}</div></div></div></td>
                        <td><div class="max-w-[220px] truncate text-sm text-slate-700">{{ $log->vendor_name ?? 'System-wide' }}</div></td>
                        <td><span class="badge-neutral">{{ ucwords(str_replace('_',' ',$log->event_type)) }}</span></td>
                        <td><p class="max-w-xl text-sm leading-6 text-slate-600">{{ $log->description }}</p></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z"/></svg></div><h2 class="font-semibold text-slate-900">No audit events found</h2><p class="mt-1 text-sm text-slate-500">Adjust filters or complete workflow actions to generate evidence.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $logs->links() }}</div>
</x-layout>
