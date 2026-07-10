<x-layout :title="'Assurance View'">
    @php $complianceRate = $totalVendors > 0 ? round(($fullyCompliant / $totalVendors) * 100) : 0; @endphp
    <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div><div class="page-kicker">Independent assurance</div><h1 class="page-title">Read-only assurance view</h1><p class="page-subtitle">Inspect vendor compliance posture, document decisions, and immutable audit evidence without changing operational records.</p></div>
        <div class="flex gap-2"><a href="{{ route('auditor.vendors') }}" class="btn-secondary">Browse vendors</a><a href="{{ route('auditor.audit-log') }}" class="btn-primary">Open audit trail</a></div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card"><div class="metric-label">Active vendors</div><div class="metric-value">{{ $totalVendors }}</div><div class="metric-meta"><span>auditable portfolio</span></div></div>
        <div class="metric-card"><div class="metric-label">Compliance rate</div><div class="metric-value text-emerald-600">{{ $complianceRate }}%</div><div class="metric-meta"><span>{{ $fullyCompliant }} fully compliant</span></div></div>
        <div class="metric-card"><div class="metric-label">Non-compliant</div><div class="metric-value text-red-600">{{ $nonCompliant }}</div><div class="metric-meta"><span>requires remediation evidence</span></div></div>
        <div class="metric-card"><div class="metric-label">Access mode</div><div class="mt-4 text-xl font-bold text-indigo-700">Read only</div><div class="metric-meta"><span>no mutation permissions</span></div></div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
        <div class="panel overflow-hidden">
            <div class="panel-header"><div><h2 class="panel-title">Recent audited activity</h2><p class="panel-caption">Latest recorded compliance and workflow events.</p></div><a href="{{ route('auditor.audit-log') }}" class="text-xs font-semibold text-indigo-600">Full history →</a></div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentLogs as $log)
                    <div class="flex items-start gap-3 px-5 py-4"><div class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-indigo-500 ring-4 ring-indigo-50"></div><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><div class="font-medium text-slate-800">{{ ucwords(str_replace('_',' ',$log->event_type)) }}</div><time class="text-xs text-slate-400">{{ $log->occurred_at->diffForHumans() }}</time></div><div class="mt-1 text-sm text-slate-500">{{ $log->vendor_name ?? 'System' }}</div></div></div>
                @empty
                    <div class="empty-state py-10"><p class="text-sm text-slate-500">No audited activity is available.</p></div>
                @endforelse
            </div>
        </div>

        <div class="space-y-5">
            <div class="panel p-5"><div class="grid h-11 w-11 place-items-center rounded-2xl bg-indigo-50 text-indigo-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-4.4 0-8 4-8 4s3.6 4 8 4 8-4 8-4-3.6-4-8-4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg></div><h2 class="mt-4 font-semibold text-slate-900">Controlled read-only access</h2><p class="mt-2 text-sm leading-6 text-slate-500">Auditor permissions allow portfolio inspection and evidence export while blocking vendor changes, document uploads, and review decisions.</p></div>
            <div class="panel p-5"><h2 class="panel-title">Assurance shortcuts</h2><div class="mt-4 space-y-2"><a href="{{ route('auditor.vendors') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-indigo-200 hover:bg-indigo-50">Vendor records <span>→</span></a><a href="{{ route('auditor.audit-log') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 hover:border-indigo-200 hover:bg-indigo-50">Audit evidence <span>→</span></a></div></div>
        </div>
    </section>
</x-layout>
