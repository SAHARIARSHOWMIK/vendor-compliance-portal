<x-layout :title="'Compliance Command Center'">
    <div class="mb-7 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="page-kicker">Operations overview</div>
            <h1 class="page-title">Compliance command center</h1>
            <p class="page-subtitle">Monitor onboarding progress, evidence quality, review workload, expiry exposure, and high-risk vendor actions from one workspace.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.index') }}" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3"/></svg>
                Open reports
            </a>
            <a href="{{ route('reviewer.queue') }}" class="btn-primary">
                Review {{ $pendingReviewDocs }} documents
            </a>
        </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.vendors.index') }}" class="metric-card">
            <div class="metric-icon bg-indigo-50 text-indigo-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 15h.01M15 15h.01"/></svg></div>
            <div class="metric-label">Active vendors</div>
            <div class="metric-value">{{ number_format($totalVendors) }}</div>
            <div class="metric-meta"><span class="text-emerald-600 font-semibold">{{ $fullyCompliant }} compliant</span><span>across portfolio</span></div>
        </a>
        <a href="{{ route('admin.vendors.index', ['compliance_status' => 'fully_compliant']) }}" class="metric-card">
            <div class="metric-icon bg-emerald-50 text-emerald-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.6-4A11.95 11.95 0 0112 3a11.95 11.95 0 01-8.6 3C3.14 7.16 3 8.37 3 9.6 3 15.1 6.84 19.7 12 21c5.16-1.3 9-5.9 9-11.4 0-1.23-.14-2.44-.4-3.6z"/></svg></div>
            <div class="metric-label">Compliance rate</div>
            <div class="metric-value">{{ $complianceRate }}%</div>
            <div class="metric-meta"><span>Average score</span><span class="font-semibold text-slate-700">{{ $averageScore }}%</span></div>
        </a>
        <a href="{{ route('reviewer.queue') }}" class="metric-card">
            <div class="metric-icon bg-amber-50 text-amber-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z"/></svg></div>
            <div class="metric-label">Review workload</div>
            <div class="metric-value">{{ number_format($pendingReviewDocs) }}</div>
            <div class="metric-meta"><span class="{{ $overdueReviewDocs > 0 ? 'text-red-600' : 'text-emerald-600' }} font-semibold">{{ $overdueReviewDocs }} overdue</span><span>{{ $documentsReviewedThisMonth }} completed this month</span></div>
        </a>
        <a href="{{ route('admin.reports.expiring-documents') }}" class="metric-card">
            <div class="metric-icon bg-red-50 text-red-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="metric-label">Exposure requiring action</div>
            <div class="metric-value">{{ $highRiskVendors + $expiringSoonDocs }}</div>
            <div class="metric-meta"><span class="font-semibold text-red-600">{{ $highRiskVendors }} high-risk</span><span>{{ $expiringSoonDocs }} expiring soon</span></div>
        </a>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Vendor lifecycle pipeline</h2>
                    <p class="panel-caption">Current distribution across onboarding and assurance stages.</p>
                </div>
                <a href="{{ route('admin.vendors.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">View portfolio →</a>
            </div>
            <div class="panel-body">
                @php $maxPipeline = max(1, (int) collect($statusPipeline)->max()); @endphp
                <div class="space-y-4">
                    @foreach ($statusPipeline as $label => $count)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $label }}</span>
                                <span class="text-xs font-semibold text-slate-500">{{ $count }} vendors</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-bar" data-progress="{{ ($count / $maxPipeline) * 100 }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Portfolio composition</h2>
                    <p class="panel-caption">Vendor category concentration.</p>
                </div>
            </div>
            <div class="panel-body space-y-4">
                @forelse ($categoryDistribution as $category => $count)
                    @php $share = $totalVendors > 0 ? round(($count / $totalVendors) * 100) : 0; @endphp
                    <div class="flex items-center gap-3">
                        <div class="grid h-10 w-10 place-items-center rounded-xl bg-slate-100 text-xs font-bold text-slate-600">{{ strtoupper(substr($category, 0, 2)) }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-slate-800">{{ $category }}</span>
                                <span class="text-xs font-semibold text-slate-500">{{ $share }}%</span>
                            </div>
                            <div class="mt-1.5 progress-track h-1.5"><div class="progress-bar" data-progress="{{ $share }}"></div></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No vendor category data yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="panel overflow-hidden">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Priority remediation</h2>
                    <p class="panel-caption">High-risk or low-scoring vendors needing attention.</p>
                </div>
                <a href="{{ route('admin.vendors.index', ['score_band' => 'critical']) }}" class="text-xs font-semibold text-indigo-600">Open filtered view</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($priorityVendors as $vendor)
                    <a href="{{ route('admin.vendors.show', $vendor) }}" class="flex items-center gap-4 px-5 py-4 transition hover:bg-slate-50">
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $vendor->risk_level === 'high' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }} text-sm font-bold">{{ strtoupper(substr($vendor->name, 0, 2)) }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="truncate font-semibold text-slate-900">{{ $vendor->name }}</div>
                                <span class="badge {{ $vendor->risk_level === 'high' ? 'risk-high' : 'risk-medium' }}">{{ ucfirst($vendor->risk_level) }} risk</span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span>{{ ucwords(str_replace('_', ' ', $vendor->compliance_status ?? $vendor->status)) }}</span>
                                <span>•</span>
                                <span>{{ $vendor->assignedReviewer?->name ?? 'Unassigned' }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold {{ $vendor->compliance_score < 60 ? 'text-red-600' : 'text-amber-600' }}">{{ $vendor->compliance_score }}%</div>
                            <div class="text-[11px] text-slate-400">score</div>
                        </div>
                    </a>
                @empty
                    <div class="empty-state py-10"><p class="text-sm text-slate-500">No priority vendors. Portfolio risk is currently controlled.</p></div>
                @endforelse
            </div>
        </div>

        <div class="panel overflow-hidden">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Upcoming evidence expiry</h2>
                    <p class="panel-caption">Approved documents reaching renewal windows.</p>
                </div>
                <a href="{{ route('admin.reports.expiring-documents') }}" class="text-xs font-semibold text-indigo-600">View report →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($upcomingExpiries as $document)
                    @php $days = $document->daysUntilExpiry(); @endphp
                    <div class="flex items-center gap-4 px-5 py-4">
                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $days !== null && $days <= 14 ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12H4V7a2 2 0 012-2z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-slate-900">{{ $document->documentType->name }}</div>
                            <div class="mt-1 truncate text-xs text-slate-500">{{ $document->vendor->name }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold {{ $days !== null && $days <= 14 ? 'text-red-600' : 'text-amber-600' }}">{{ $days }} days</div>
                            <div class="text-[11px] text-slate-400">{{ $document->expiry_date?->format('d M Y') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state py-10"><p class="text-sm text-slate-500">No documents expire in the next 60 days.</p></div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[.8fr_1.2fr]">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Compliance score distribution</h2>
                    <p class="panel-caption">Concentration by score band.</p>
                </div>
            </div>
            <div class="panel-body space-y-4">
                @foreach ($scoreDistribution as $range => $count)
                    @php $share = $totalVendors > 0 ? round(($count / $totalVendors) * 100) : 0; @endphp
                    <div>
                        <div class="mb-1.5 flex justify-between text-xs"><span class="font-medium text-slate-600">{{ $range }}%</span><span class="text-slate-400">{{ $count }} · {{ $share }}%</span></div>
                        <div class="progress-track h-2"><div class="progress-bar" data-progress="{{ $share }}"></div></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel overflow-hidden">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Recent compliance activity</h2>
                    <p class="panel-caption">Immutable workflow events and reviewer actions.</p>
                </div>
                <a href="{{ route('admin.reports.audit-log') }}" class="text-xs font-semibold text-indigo-600">Audit trail →</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentActivity as $log)
                    <div class="flex gap-3 px-5 py-3.5">
                        <div class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-indigo-500 ring-4 ring-indigo-50"></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="truncate text-sm font-medium text-slate-800">{{ ucwords(str_replace('_', ' ', $log->event_type)) }}</div>
                                <time class="whitespace-nowrap text-[11px] text-slate-400">{{ $log->occurred_at->diffForHumans() }}</time>
                            </div>
                            <div class="mt-0.5 truncate text-xs text-slate-500">{{ $log->vendor_name ?? 'System' }} · {{ $log->actor_name ?? 'Automated process' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state py-10"><p class="text-sm text-slate-500">No compliance activity has been recorded yet.</p></div>
                @endforelse
            </div>
        </div>
    </section>
</x-layout>
