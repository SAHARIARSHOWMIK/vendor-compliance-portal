<x-layout :title="'Vendor Portfolio'">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="page-kicker">Third-party portfolio</div>
            <h1 class="page-title">Vendor portfolio</h1>
            <p class="page-subtitle">Search, segment, and prioritize vendors by lifecycle status, compliance health, reviewer ownership, and risk exposure.</p>
        </div>
        @can('create', App\Models\Vendor::class)
            <a href="{{ route('admin.vendors.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                Register vendor
            </a>
        @endcan
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.vendors.index') }}" class="stat-chip justify-between py-3"><span>Total active</span><strong class="text-base text-slate-900">{{ $portfolio['total'] }}</strong></a>
        <a href="{{ route('admin.vendors.index', ['risk_level' => 'high']) }}" class="stat-chip justify-between py-3"><span>High risk</span><strong class="text-base text-red-600">{{ $portfolio['high_risk'] }}</strong></a>
        <a href="{{ route('admin.vendors.index', ['score_band' => 'critical']) }}" class="stat-chip justify-between py-3"><span>Needs action</span><strong class="text-base text-amber-600">{{ $portfolio['needs_action'] }}</strong></a>
        <a href="{{ route('admin.vendors.index', ['compliance_status' => 'fully_compliant']) }}" class="stat-chip justify-between py-3"><span>Fully compliant</span><strong class="text-base text-emerald-600">{{ $portfolio['fully_compliant'] }}</strong></a>
    </div>

    <form method="GET" class="filter-bar">
        <div class="min-w-[240px] flex-1">
            <label class="field-label" for="search">Search portfolio</label>
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.4-4.4m2.4-5.1a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/></svg>
                <input id="search" name="search" value="{{ request('search') }}" class="field-control w-full pl-9" placeholder="Name, registration, contact, email…">
            </div>
        </div>
        <div>
            <label class="field-label">Risk</label>
            <select name="risk_level" class="field-control">
                <option value="">All risks</option>
                @foreach (['low', 'medium', 'high'] as $risk)<option value="{{ $risk }}" @selected(request('risk_level') === $risk)>{{ ucfirst($risk) }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Lifecycle</label>
            <select name="status" class="field-control">
                <option value="">All stages</option>
                @foreach (['draft','invited','registered','documents_pending','under_review','correction_required','partially_approved','fully_compliant','expiring_soon','non_compliant','suspended','archived'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label">Score band</label>
            <select name="score_band" class="field-control">
                <option value="">All scores</option>
                <option value="critical" @selected(request('score_band') === 'critical')>Critical · below 40</option>
                <option value="needs_attention" @selected(request('score_band') === 'needs_attention')>Needs attention · 40–74</option>
                <option value="healthy" @selected(request('score_band') === 'healthy')>Healthy · 75–99</option>
                <option value="complete" @selected(request('score_band') === 'complete')>Complete · 100</option>
            </select>
        </div>
        <div>
            <label class="field-label">Sort</label>
            <select name="sort" class="field-control">
                <option value="">Operational priority</option>
                <option value="score_asc" @selected(request('sort') === 'score_asc')>Lowest score first</option>
                <option value="score_desc" @selected(request('sort') === 'score_desc')>Highest score first</option>
                <option value="recent" @selected(request('sort') === 'recent')>Recently updated</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary btn-xs">Apply</button>
            <a href="{{ route('admin.vendors.index') }}" class="btn-secondary btn-xs">Reset</a>
        </div>
    </form>

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Vendor</th>
                    <th>Risk</th>
                    <th>Lifecycle</th>
                    <th>Compliance</th>
                    <th>Score</th>
                    <th>Owner</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vendors as $vendor)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-slate-100 to-indigo-50 text-xs font-bold text-slate-700">{{ strtoupper(substr($vendor->name, 0, 2)) }}</div>
                                <div class="min-w-0">
                                    <a class="block truncate font-semibold text-slate-900 hover:text-indigo-700" href="{{ route('admin.vendors.show', $vendor) }}">{{ $vendor->name }}</a>
                                    <div class="mt-0.5 truncate text-xs text-slate-500">{{ $vendor->registration_number ?: 'No registration number' }} · {{ ucwords(str_replace('_', ' ', $vendor->category)) }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge {{ $vendor->risk_level === 'high' ? 'risk-high' : ($vendor->risk_level === 'medium' ? 'risk-medium' : 'risk-low') }}"><span class="badge-dot"></span>{{ ucfirst($vendor->risk_level) }}</span></td>
                        <td><x-vendor-status-badge :status="$vendor->status" /></td>
                        <td>@if ($vendor->compliance_status)<x-compliance-badge :status="$vendor->compliance_status" />@else<span class="badge-neutral">Not assessed</span>@endif</td>
                        <td class="min-w-[150px]">
                            <div class="flex items-center gap-3">
                                <div class="progress-track min-w-20 flex-1"><div class="progress-bar" data-progress="{{ $vendor->compliance_score }}"></div></div>
                                <span class="w-10 text-right font-bold {{ $vendor->compliance_score < 60 ? 'text-red-600' : ($vendor->compliance_score < 85 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $vendor->compliance_score }}%</span>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm text-slate-700">{{ $vendor->assignedReviewer?->name ?? 'Unassigned' }}</div>
                            <div class="text-xs text-slate-400">{{ $vendor->updated_at->diffForHumans() }}</div>
                        </td>
                        <td class="text-right"><a class="btn-secondary btn-xs" href="{{ route('admin.vendors.show', $vendor) }}">Open record</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m21 21-4.4-4.4m2.4-5.1a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/></svg></div><h2 class="font-semibold text-slate-900">No vendors match these filters</h2><p class="mt-1 text-sm text-slate-500">Try removing filters or register a new vendor.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $vendors->links() }}</div>
</x-layout>
