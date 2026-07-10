<x-layout :title="'My Compliance · ' . $vendor->name">
    @php
        $items = collect($checklist);
        $missing = $items->where('is_missing', true)->count();
        $approved = $items->where('is_approved', true)->count();
        $needsAction = $items->filter(fn ($item) => $item['is_rejected'] || $item['is_expiring'])->count();
        $inReview = $items->filter(fn ($item) => $item['document'] && in_array($item['document']->status, ['uploaded','reuploaded','under_review']))->count();
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="page-kicker">Vendor self-service</div>
            <h1 class="page-title">My compliance workspace</h1>
            <p class="page-subtitle">Track every required document, respond to reviewer feedback, and keep evidence current for {{ $vendor->name }}.</p>
        </div>
        <a href="{{ route('vendor-portal.documents.upload-form', $vendor) }}" class="btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
            Upload evidence
        </a>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card"><div class="metric-label">Compliance score</div><div class="metric-value {{ $vendor->compliance_score < 60 ? 'text-red-600' : ($vendor->compliance_score < 85 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $vendor->compliance_score }}%</div><div class="metric-meta"><x-compliance-badge :status="$vendor->compliance_status ?? 'documents_missing'" /></div></div>
        <div class="metric-card"><div class="metric-label">Approved</div><div class="metric-value text-emerald-600">{{ $approved }}</div><div class="metric-meta"><span>of {{ $items->count() }} required documents</span></div></div>
        <div class="metric-card"><div class="metric-label">In review</div><div class="metric-value text-indigo-600">{{ $inReview }}</div><div class="metric-meta"><span>awaiting reviewer decisions</span></div></div>
        <div class="metric-card"><div class="metric-label">Needs action</div><div class="metric-value {{ ($missing + $needsAction) > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $missing + $needsAction }}</div><div class="metric-meta"><span>{{ $missing }} missing · {{ $needsAction }} correction/expiry</span></div></div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.3fr_.7fr]">
        <div class="panel overflow-hidden">
            <div class="panel-header">
                <div><h2 class="panel-title">Required evidence checklist</h2><p class="panel-caption">Documents required for {{ ucwords(str_replace('_', ' ', $vendor->category)) }}.</p></div>
                <span class="badge-neutral">{{ $approved }}/{{ $items->count() }} approved</span>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($checklist as $item)
                    @php
                        $doc = $item['document'];
                        $actionRequired = $item['is_missing'] || $item['is_rejected'] || $item['is_expiring'];
                    @endphp
                    <article class="px-5 py-5 {{ $actionRequired ? 'bg-amber-50/20' : '' }}">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $item['is_approved'] ? 'bg-emerald-50 text-emerald-700' : ($actionRequired ? 'bg-amber-50 text-amber-700' : 'bg-indigo-50 text-indigo-700') }}">
                                @if ($item['is_approved'])
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z"/></svg>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-slate-900">{{ $item['document_type']->name }}</h3>@if ($item['document_type']->requires_expiry_date)<span class="badge-neutral">Expiry required</span>@endif</div>
                                @if ($doc)
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500"><span>v{{ $doc->version_number }}</span><span>•</span><span class="truncate">{{ $doc->original_filename }}</span>@if ($doc->expiry_date)<span>•</span><span>Expires {{ $doc->expiry_date->format('d M Y') }}</span>@endif</div>
                                @else
                                    <p class="mt-1 text-xs text-slate-500">No document has been uploaded for this requirement.</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                                @if ($item['is_missing'])
                                    <span class="badge-danger"><span class="badge-dot"></span>Missing</span>
                                    <a class="btn-primary btn-xs" href="{{ route('vendor-portal.documents.upload-form', [$vendor, 'type_id' => $item['document_type']->id]) }}">Upload</a>
                                @else
                                    <x-document-status-badge :status="$doc->status" />
                                    @if ($item['is_rejected'] || $item['is_expiring'])<a class="btn-primary btn-xs" href="{{ route('vendor-portal.documents.upload-form', [$vendor, 'type_id' => $item['document_type']->id]) }}">Replace file</a>@endif
                                    <a class="btn-secondary btn-xs" href="{{ route('vendor-portal.documents.download', $doc) }}">Download</a>
                                @endif
                            </div>
                        </div>
                        @if ($doc?->review_comment && ! $item['is_approved'])
                            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3"><div class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-700">Reviewer guidance</div><p class="mt-1 text-sm leading-6 text-amber-900">{{ $doc->review_comment }}</p></div>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>

        <aside class="space-y-5">
            <div class="panel p-5">
                <div class="flex items-center justify-between"><h2 class="panel-title">Overall progress</h2><span class="text-xl font-bold text-slate-950">{{ $vendor->compliance_score }}%</span></div>
                <div class="mt-4 progress-track h-3"><div class="progress-bar" data-progress="{{ $vendor->compliance_score }}"></div></div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center"><div class="rounded-xl bg-emerald-50 p-3"><div class="text-lg font-bold text-emerald-700">{{ $approved }}</div><div class="text-[11px] text-emerald-700/70">Approved</div></div><div class="rounded-xl bg-indigo-50 p-3"><div class="text-lg font-bold text-indigo-700">{{ $inReview }}</div><div class="text-[11px] text-indigo-700/70">In review</div></div><div class="rounded-xl bg-red-50 p-3"><div class="text-lg font-bold text-red-700">{{ $missing + $needsAction }}</div><div class="text-[11px] text-red-700/70">Action</div></div></div>
            </div>

            <div class="panel">
                <div class="panel-header"><div><h2 class="panel-title">Recent submissions</h2><p class="panel-caption">Latest evidence activity.</p></div></div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recentUpdates as $update)
                        <div class="px-5 py-4"><div class="flex items-center justify-between gap-3"><div class="min-w-0"><div class="truncate text-sm font-semibold text-slate-800">{{ $update->documentType->name }}</div><div class="mt-1 text-xs text-slate-400">v{{ $update->version_number }} · {{ $update->uploaded_at?->diffForHumans() }}</div></div><x-document-status-badge :status="$update->status" /></div></div>
                    @empty
                        <div class="empty-state py-8"><p class="text-sm text-slate-500">No submissions yet.</p></div>
                    @endforelse
                </div>
            </div>

            <div class="panel border-indigo-200 bg-gradient-to-br from-indigo-50 to-cyan-50 p-5"><div class="grid h-10 w-10 place-items-center rounded-xl bg-white text-indigo-700 shadow-sm"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M5 19h14L12 4 5 19z"/></svg></div><h2 class="mt-4 font-semibold text-slate-900">Keep evidence current</h2><p class="mt-2 text-sm leading-6 text-slate-600">Upload replacements before expiry and respond to reviewer comments promptly to prevent compliance interruptions.</p></div>
        </aside>
    </section>
</x-layout>
