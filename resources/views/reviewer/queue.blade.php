<x-layout :title="'Review Queue'">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="page-kicker">Human approval workspace</div>
            <h1 class="page-title">Evidence review queue</h1>
            <p class="page-subtitle">Prioritized by vendor risk, expiry proximity, and waiting time. Every decision is versioned, communicated, and retained in the audit trail.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (auth()->user()->isReviewer())
                <a href="{{ route('reviewer.queue', array_merge(request()->except('page'), ['show_all' => request()->boolean('show_all') ? 0 : 1])) }}" class="btn-secondary">
                    {{ request()->boolean('show_all') ? 'Show my assignments' : 'Show all eligible' }}
                </a>
            @endif
        </div>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('reviewer.queue') }}" class="stat-chip justify-between py-3"><span>Pending review</span><strong class="text-base text-slate-900">{{ $queueSummary['total'] }}</strong></a>
        <a href="{{ route('reviewer.queue', ['risk_level' => 'high']) }}" class="stat-chip justify-between py-3"><span>High-risk vendors</span><strong class="text-base text-red-600">{{ $queueSummary['high_risk'] }}</strong></a>
        <a href="{{ route('reviewer.queue', ['priority' => 'overdue']) }}" class="stat-chip justify-between py-3"><span>Overdue &gt; 3 days</span><strong class="text-base text-amber-600">{{ $queueSummary['overdue'] }}</strong></a>
        <a href="{{ route('reviewer.queue', ['priority' => 'expiring']) }}" class="stat-chip justify-between py-3"><span>Expiry priority</span><strong class="text-base text-indigo-600">{{ $queueSummary['expiring'] }}</strong></a>
    </div>

    <form method="GET" class="filter-bar">
        @if (request()->boolean('show_all'))<input type="hidden" name="show_all" value="1">@endif
        <div class="min-w-[260px] flex-1">
            <label class="field-label">Search queue</label>
            <div class="relative"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.4-4.4m2.4-5.1a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/></svg><input class="field-control w-full pl-9" name="search" value="{{ request('search') }}" placeholder="Vendor or filename…"></div>
        </div>
        <div>
            <label class="field-label">Vendor</label>
            <select name="vendor_id" class="field-control"><option value="">All vendors</option>@foreach ($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->name }}</option>@endforeach</select>
        </div>
        <div>
            <label class="field-label">Risk</label>
            <select name="risk_level" class="field-control"><option value="">All risk levels</option>@foreach (['high','medium','low'] as $risk)<option value="{{ $risk }}" @selected(request('risk_level') === $risk)>{{ ucfirst($risk) }}</option>@endforeach</select>
        </div>
        <div>
            <label class="field-label">Priority</label>
            <select name="priority" class="field-control"><option value="">All priorities</option><option value="overdue" @selected(request('priority') === 'overdue')>Overdue</option><option value="expiring" @selected(request('priority') === 'expiring')>Expiring soon</option></select>
        </div>
        <div class="flex gap-2"><button class="btn-primary btn-xs">Apply</button><a href="{{ route('reviewer.queue') }}" class="btn-secondary btn-xs">Reset</a></div>
    </form>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead><tr><th>Vendor & document</th><th>Risk</th><th>Submitted</th><th>Expiry</th><th>Priority</th><th>Owner</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            @forelse ($documents as $document)
                @php
                    $ageDays = $document->uploaded_at ? (int) $document->uploaded_at->diffInDays(now()) : 0;
                    $daysToExpiry = $document->daysUntilExpiry();
                    $isHighPriority = $document->vendor->risk_level === 'high' || $ageDays >= 3 || ($daysToExpiry !== null && $daysToExpiry <= 30);
                @endphp
                <tr class="{{ $isHighPriority ? 'bg-red-50/20' : '' }}">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $document->vendor->risk_level === 'high' ? 'bg-red-50 text-red-700' : 'bg-indigo-50 text-indigo-700' }}"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z"/></svg></div>
                            <div class="min-w-0"><a href="{{ route('reviewer.documents.show', $document) }}" class="block truncate font-semibold text-slate-900 hover:text-indigo-700">{{ $document->documentType->name }}</a><div class="mt-0.5 truncate text-xs text-slate-500">{{ $document->vendor->name }} · v{{ $document->version_number }} · {{ $document->original_filename }}</div></div>
                        </div>
                    </td>
                    <td><span class="badge {{ $document->vendor->risk_level === 'high' ? 'risk-high' : ($document->vendor->risk_level === 'medium' ? 'risk-medium' : 'risk-low') }}"><span class="badge-dot"></span>{{ ucfirst($document->vendor->risk_level) }}</span></td>
                    <td><div class="text-sm font-medium text-slate-700">{{ $document->uploaded_at?->format('d M Y') }}</div><div class="text-xs {{ $ageDays >= 3 ? 'text-red-600 font-semibold' : 'text-slate-400' }}">Waiting {{ $ageDays }} {{ \Illuminate\Support\Str::plural('day', $ageDays) }}</div></td>
                    <td>@if ($document->expiry_date)<div class="text-sm font-medium {{ $daysToExpiry !== null && $daysToExpiry <= 30 ? 'text-red-600' : 'text-slate-700' }}">{{ $document->expiry_date->format('d M Y') }}</div><div class="text-xs text-slate-400">{{ $daysToExpiry }} days</div>@else<span class="text-slate-400">Not applicable</span>@endif</td>
                    <td>@if ($isHighPriority)<span class="badge-danger"><span class="badge-dot"></span>Priority review</span>@else<span class="badge-neutral">Standard</span>@endif</td>
                    <td><div class="text-sm text-slate-700">{{ $document->vendor->assignedReviewer?->name ?? 'Unassigned' }}</div><x-document-status-badge :status="$document->status" /></td>
                    <td class="text-right"><a href="{{ route('reviewer.documents.show', $document) }}" class="btn-primary btn-xs">Review evidence</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div><h2 class="font-semibold text-slate-900">Review queue is clear</h2><p class="mt-1 text-sm text-slate-500">No submitted documents match the current scope.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $documents->links() }}</div>
</x-layout>
