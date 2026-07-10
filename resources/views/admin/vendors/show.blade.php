<x-layout :title="$vendor->name">
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs font-medium text-slate-500">
                <a href="{{ route('admin.vendors.index') }}" class="hover:text-indigo-700">Vendor portfolio</a>
                <span>/</span>
                <span>{{ $vendor->registration_number ?: 'Vendor record' }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="page-title mt-0">{{ $vendor->name }}</h1>
                <span class="badge {{ $vendor->risk_level === 'high' ? 'risk-high' : ($vendor->risk_level === 'medium' ? 'risk-medium' : 'risk-low') }}"><span class="badge-dot"></span>{{ ucfirst($vendor->risk_level) }} risk</span>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-vendor-status-badge :status="$vendor->status" />
                @if ($vendor->compliance_status)<x-compliance-badge :status="$vendor->compliance_status" />@endif
                <span class="text-xs text-slate-500">Updated {{ $vendor->updated_at->diffForHumans() }}</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $vendor)<a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn-secondary">Edit profile</a>@endcan
            @can('invite', $vendor)<a href="{{ route('admin.vendors.invite-form', $vendor) }}" class="btn-primary">Invite vendor user</a>@endcan
        </div>
    </div>

    @php
        $requiredCount = $requiredDocTypes->count();
        $uploadedCount = $vendor->documents->whereIn('document_type_id', $requiredDocTypes->pluck('id'))->count();
        $approvedCount = $vendor->documents->whereIn('document_type_id', $requiredDocTypes->pluck('id'))->where('status', 'approved')->count();
        $missingCount = max(0, $requiredCount - $uploadedCount);
        $reviewCount = $vendor->documents->whereIn('status', ['uploaded', 'reuploaded', 'under_review'])->count();
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card">
            <div class="metric-label">Compliance score</div>
            <div class="metric-value {{ $vendor->compliance_score < 60 ? 'text-red-600' : ($vendor->compliance_score < 85 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $vendor->compliance_score }}%</div>
            <div class="metric-meta"><span>{{ $approvedCount }} approved</span><span>of {{ $requiredCount }} required</span></div>
        </div>
        <div class="metric-card"><div class="metric-label">Missing evidence</div><div class="metric-value">{{ $missingCount }}</div><div class="metric-meta"><span>{{ $uploadedCount }} submitted</span><span>{{ $reviewCount }} in review</span></div></div>
        <div class="metric-card"><div class="metric-label">Review owner</div><div class="mt-4 truncate text-xl font-bold text-slate-950">{{ $vendor->assignedReviewer?->name ?? 'Unassigned' }}</div><div class="metric-meta"><span>{{ $vendor->assignedReviewer?->email ?? 'Assign a reviewer to continue' }}</span></div></div>
        <div class="metric-card"><div class="metric-label">Vendor users</div><div class="metric-value">{{ $vendor->vendorUsers->count() }}</div><div class="metric-meta"><span>{{ $vendor->vendorUsers->where('invitation_status', 'accepted')->count() }} active</span><span>{{ $vendor->vendorUsers->where('invitation_status', 'pending')->count() }} pending</span></div></div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
        <div class="space-y-6">
            <div class="panel">
                <div class="panel-header">
                    <div><h2 class="panel-title">Evidence checklist</h2><p class="panel-caption">Required documents for {{ ucwords(str_replace('_', ' ', $vendor->category)) }}.</p></div>
                    @can('update', $vendor)<a href="{{ route('admin.vendors.edit', $vendor) }}" class="text-xs font-semibold text-indigo-600">Manage requirements →</a>@endcan
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($requiredDocTypes as $docType)
                        @php $document = $vendor->documents->firstWhere('document_type_id', $docType->id); @endphp
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $document?->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($document ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                                @if ($document?->status === 'approved')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z"/></svg>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-slate-900">{{ $docType->name }}</div>
                                <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                                    @if ($document)
                                        <span>v{{ $document->version_number }}</span><span>•</span><span class="truncate">{{ $document->original_filename }}</span>
                                        @if ($document->expiry_date)<span>•</span><span>Expires {{ $document->expiry_date->format('d M Y') }}</span>@endif
                                    @else
                                        <span>No file submitted</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 sm:ml-auto">
                                @if ($document)
                                    <x-document-status-badge :status="$document->status" />
                                    @if (in_array($document->status, ['uploaded','reuploaded','under_review']))<a href="{{ route('reviewer.documents.show', $document) }}" class="btn-secondary btn-xs">Review</a>@endif
                                @else
                                    <span class="badge-danger"><span class="badge-dot"></span>Missing</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state py-10"><p class="text-sm text-slate-500">No document requirements are configured for this category.</p></div>
                    @endforelse
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><div><h2 class="panel-title">Vendor profile</h2><p class="panel-caption">Identity, ownership, and contact information.</p></div></div>
                <div class="panel-body grid gap-x-8 gap-y-5 sm:grid-cols-2">
                    @foreach ([
                        ['Registration number', $vendor->registration_number ?: '—'],
                        ['Category', ucwords(str_replace('_', ' ', $vendor->category))],
                        ['Contact person', $vendor->contact_person ?: '—'],
                        ['Email', $vendor->email ?: '—'],
                        ['Phone', $vendor->phone ?: '—'],
                        ['Country', $vendor->country ?: '—'],
                    ] as [$label, $value])
                        <div><div class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</div><div class="mt-1 text-sm font-medium text-slate-800">{{ $value }}</div></div>
                    @endforeach
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><div><h2 class="panel-title">Activity timeline</h2><p class="panel-caption">Immutable events associated with this vendor record.</p></div></div>
                <div class="panel-body">
                    <ol class="relative ml-2 border-l border-slate-200">
                        @forelse ($vendor->auditLogs as $log)
                            <li class="relative mb-6 ml-5 last:mb-0">
                                <span class="timeline-dot"></span>
                                <div class="flex flex-wrap items-center justify-between gap-2"><div class="text-sm font-semibold text-slate-800">{{ ucwords(str_replace('_', ' ', $log->event_type)) }}</div><time class="text-[11px] text-slate-400">{{ $log->occurred_at->format('d M Y H:i') }}</time></div>
                                <p class="mt-1 text-sm leading-6 text-slate-500">{{ $log->description }}</p>
                            </li>
                        @empty
                            <li class="ml-5 text-sm text-slate-500">No activity has been recorded yet.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>

        <aside class="space-y-5">
            <div class="panel p-5">
                <div class="flex items-center justify-between"><h2 class="panel-title">Compliance posture</h2><x-compliance-badge :status="$vendor->compliance_status ?? 'documents_missing'" /></div>
                <div class="mt-5 flex items-end justify-between"><div><div class="text-4xl font-bold tracking-tight text-slate-950">{{ $vendor->compliance_score }}%</div><div class="mt-1 text-xs text-slate-500">current score</div></div><div class="text-right text-xs text-slate-500"><div>{{ $approvedCount }} approved</div><div>{{ $missingCount }} missing</div><div>{{ $reviewCount }} in review</div></div></div>
                <div class="mt-4 progress-track"><div class="progress-bar" data-progress="{{ $vendor->compliance_score }}"></div></div>
            </div>

            @can('assignReviewer', $vendor)
                <div class="panel p-5">
                    <h2 class="panel-title">Review ownership</h2>
                    <p class="panel-caption">Assign responsibility for evidence decisions.</p>
                    <form class="mt-4 space-y-3" method="POST" action="{{ route('admin.vendors.assign-reviewer', $vendor) }}">
                        @csrf
                        <select name="reviewer_id" class="field-control w-full">
                            <option value="">Not assigned</option>
                            @foreach ($reviewers as $reviewer)<option value="{{ $reviewer->id }}" @selected($vendor->assigned_reviewer_id == $reviewer->id)>{{ $reviewer->name }}</option>@endforeach
                        </select>
                        <button class="btn-primary w-full" type="submit">Update owner</button>
                    </form>
                </div>
            @endcan

            <div class="panel p-5">
                <div class="flex items-center justify-between"><h2 class="panel-title">Vendor access</h2><span class="badge-neutral">{{ $vendor->vendorUsers->count() }} users</span></div>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse ($vendor->vendorUsers as $vendorUser)
                        <div class="flex items-center gap-3 py-3">
                            <div class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-xs font-bold text-slate-600">{{ strtoupper(substr($vendorUser->user->name,0,1)) }}</div>
                            <div class="min-w-0 flex-1"><div class="truncate text-sm font-medium text-slate-800">{{ $vendorUser->user->name }}</div><div class="truncate text-xs text-slate-400">{{ $vendorUser->user->email }}</div></div>
                            <span class="{{ $vendorUser->isAccepted() ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($vendorUser->invitation_status) }}</span>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">No vendor users have been invited.</p>
                    @endforelse
                </div>
            </div>

            @can('suspend', $vendor)
                <div class="panel border-red-200 p-5">
                    <h2 class="panel-title text-red-700">Suspend vendor</h2><p class="panel-caption">Use when active risk or contractual issues require an immediate hold.</p>
                    <form class="mt-4 space-y-3" method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">
                        @csrf
                        <textarea required name="reason" rows="3" class="field-control w-full" placeholder="State the reason for suspension…"></textarea>
                        <button class="btn-danger w-full" data-confirm="Suspend this vendor and restrict workflow access?" type="submit">Suspend vendor</button>
                    </form>
                </div>
            @endcan

            @can('reinstate', $vendor)
                <div class="panel p-5"><form method="POST" action="{{ route('admin.vendors.reinstate', $vendor) }}">@csrf<button class="btn-success w-full" type="submit">Reinstate vendor</button></form></div>
            @endcan
        </aside>
    </section>
</x-layout>
