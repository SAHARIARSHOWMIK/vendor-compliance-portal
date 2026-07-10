<x-layout :title="'Review · ' . $document->documentType->name">
    @php
        $daysToExpiry = $document->daysUntilExpiry();
        $waitingDays = $document->uploaded_at ? (int) $document->uploaded_at->diffInDays(now()) : 0;
    @endphp

    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs font-medium text-slate-500"><a href="{{ route('reviewer.queue') }}" class="hover:text-indigo-700">Review queue</a><span>/</span><span>{{ $document->vendor->name }}</span></div>
            <div class="flex flex-wrap items-center gap-3"><h1 class="page-title mt-0">{{ $document->documentType->name }}</h1><x-document-status-badge :status="$document->status" /></div>
            <p class="page-subtitle">Version {{ $document->version_number }} submitted {{ $document->uploaded_at?->diffForHumans() }} by {{ $document->uploader?->name ?? 'Vendor user' }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('vendor-portal.documents.download', $document) }}" class="btn-secondary"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>Download evidence</a>
            <a href="{{ route('admin.vendors.show', $document->vendor) }}" class="btn-soft">Open vendor record</a>
        </div>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-chip justify-between py-3"><span>Vendor risk</span><span class="badge {{ $document->vendor->risk_level === 'high' ? 'risk-high' : ($document->vendor->risk_level === 'medium' ? 'risk-medium' : 'risk-low') }}">{{ ucfirst($document->vendor->risk_level) }}</span></div>
        <div class="stat-chip justify-between py-3"><span>Waiting time</span><strong class="{{ $waitingDays >= 3 ? 'text-red-600' : 'text-slate-900' }}">{{ $waitingDays }} {{ \Illuminate\Support\Str::plural('day', $waitingDays) }}</strong></div>
        <div class="stat-chip justify-between py-3"><span>File size</span><strong class="text-slate-900">{{ number_format($document->file_size_kb) }} KB</strong></div>
        <div class="stat-chip justify-between py-3"><span>Expiry</span><strong class="{{ $daysToExpiry !== null && $daysToExpiry <= 30 ? 'text-red-600' : 'text-slate-900' }}">{{ $daysToExpiry === null ? 'Not applicable' : $daysToExpiry . ' days' }}</strong></div>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
        <div class="space-y-6">
            <div class="panel">
                <div class="panel-header"><div><h2 class="panel-title">Evidence metadata</h2><p class="panel-caption">File identity, ownership, and validity information.</p></div></div>
                <div class="panel-body grid gap-x-8 gap-y-5 sm:grid-cols-2">
                    @foreach ([
                        ['Original filename', $document->original_filename],
                        ['MIME type', $document->mime_type],
                        ['Version', 'v' . $document->version_number],
                        ['Vendor category', ucwords(str_replace('_', ' ', $document->vendor->category))],
                        ['Submitted by', $document->uploader?->name ?? '—'],
                        ['Submitted at', $document->uploaded_at?->format('d M Y H:i') ?? '—'],
                    ] as [$label, $value])
                        <div><div class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">{{ $label }}</div><div class="mt-1 break-words text-sm font-medium text-slate-800">{{ $value }}</div></div>
                    @endforeach
                    <div><div class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Expiry date</div><div class="mt-1 text-sm font-medium {{ $document->expiry_date?->isPast() ? 'text-red-600' : 'text-slate-800' }}">{{ $document->expiry_date?->format('d M Y') ?? 'Not required' }}</div></div>
                    <div><div class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Current status</div><div class="mt-1"><x-document-status-badge :status="$document->status" /></div></div>
                </div>
            </div>

            @if ($document->notes)
                <div class="panel p-5"><div class="flex gap-3"><div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h5M5 4h14v16H5z"/></svg></div><div><h2 class="panel-title">Vendor submission note</h2><p class="mt-2 text-sm leading-6 text-slate-600">{{ $document->notes }}</p></div></div></div>
            @endif

            <div class="panel">
                <div class="panel-header"><div><h2 class="panel-title">Version history</h2><p class="panel-caption">Previous evidence snapshots remain immutable and downloadable.</p></div><span class="badge-neutral">{{ $document->versions->count() + 1 }} versions</span></div>
                <div class="divide-y divide-slate-100">
                    <div class="flex items-center gap-4 bg-indigo-50/30 px-5 py-4"><div class="grid h-9 w-9 place-items-center rounded-xl bg-indigo-100 text-xs font-bold text-indigo-700">v{{ $document->version_number }}</div><div class="min-w-0 flex-1"><div class="truncate text-sm font-semibold text-slate-900">{{ $document->original_filename }}</div><div class="text-xs text-slate-500">Current · {{ $document->uploaded_at?->format('d M Y H:i') }}</div></div><x-document-status-badge :status="$document->status" /></div>
                    @foreach ($document->versions as $version)
                        <div class="flex items-center gap-4 px-5 py-4"><div class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-xs font-bold text-slate-600">v{{ $version->version_number }}</div><div class="min-w-0 flex-1"><div class="truncate text-sm font-medium text-slate-800">{{ $version->original_filename }}</div><div class="text-xs text-slate-400">{{ $version->uploaded_at?->format('d M Y H:i') }} · {{ $version->uploader?->name }}</div></div>@if ($version->status_at_snapshot)<x-document-status-badge :status="$version->status_at_snapshot" />@endif<a class="btn-secondary btn-xs" href="{{ route('vendor-portal.documents.version-download', $version) }}">Download</a></div>
                    @endforeach
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><div><h2 class="panel-title">Decision history</h2><p class="panel-caption">Append-only human review decisions and vendor-facing comments.</p></div><span class="badge-neutral">{{ $document->reviews->count() }} decisions</span></div>
                <div class="panel-body">
                    <ol class="relative ml-2 border-l border-slate-200">
                        @forelse ($document->reviews as $review)
                            <li class="relative mb-6 ml-5 last:mb-0"><span class="timeline-dot"></span><div class="flex flex-wrap items-center gap-2"><x-document-status-badge :status="$review->decision" /><span class="text-xs text-slate-500">{{ $review->reviewer?->name }} · {{ $review->reviewed_at?->format('d M Y H:i') }} · v{{ $review->document_version }}</span></div>@if ($review->comment)<p class="mt-2 rounded-xl bg-slate-50 px-3 py-2 text-sm leading-6 text-slate-600">{{ $review->comment }}</p>@endif</li>
                        @empty
                            <li class="ml-5 text-sm text-slate-500">No decisions have been recorded for this evidence yet.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>

        <aside>
            @can('decide', $document)
                <div class="panel sticky top-24 overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-950 px-5 py-5 text-white"><div class="text-xs font-semibold uppercase tracking-[0.15em] text-indigo-300">Human decision</div><h2 class="mt-1 text-xl font-bold">Complete this review</h2><p class="mt-2 text-sm leading-6 text-slate-400">Choose an outcome and provide evidence-based guidance. The vendor will receive the approved message.</p></div>
                    <div class="p-5">
                        @if ($errors->any())<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
                        <form method="POST" action="{{ route('reviewer.documents.decide', $document) }}" class="space-y-5">
                            @csrf
                            <fieldset>
                                <legend class="field-label">Decision</legend>
                                <div class="space-y-2">
                                    @foreach ([
                                        'approved' => ['Approve evidence', 'Evidence meets the requirement.', 'border-emerald-200 hover:bg-emerald-50'],
                                        'correction_requested' => ['Request correction', 'Vendor must replace or clarify the file.', 'border-amber-200 hover:bg-amber-50'],
                                        'rejected' => ['Reject evidence', 'Evidence cannot satisfy this requirement.', 'border-red-200 hover:bg-red-50'],
                                        'need_more_info' => ['Need more information', 'Ask the vendor for supporting context.', 'border-sky-200 hover:bg-sky-50'],
                                        'escalated' => ['Escalate decision', 'Send to compliance leadership.', 'border-purple-200 hover:bg-purple-50'],
                                    ] as $value => [$label, $description, $classes])
                                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition {{ $classes }}"><input type="radio" name="decision" value="{{ $value }}" class="mt-1 text-indigo-600" @checked(old('decision') === $value)><span><span class="block text-sm font-semibold text-slate-800">{{ $label }}</span><span class="mt-0.5 block text-xs leading-5 text-slate-500">{{ $description }}</span></span></label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div><label class="field-label" for="comment">Reviewer comment <span class="font-normal text-slate-400">· required for rejection/correction</span></label><textarea id="comment" name="comment" rows="5" class="field-control w-full" placeholder="Explain the evidence, risk, and required next step…">{{ old('comment') }}</textarea></div>
                            <button class="btn-primary w-full" type="submit">Record review decision</button>
                            <p class="text-center text-[11px] leading-5 text-slate-400">Submitting creates an immutable review record, updates vendor compliance, and triggers notifications.</p>
                        </form>
                    </div>
                </div>
            @else
                <div class="panel border-amber-200 p-5"><div class="badge-warning">Review unavailable</div><p class="mt-3 text-sm leading-6 text-slate-600">This document is not in a reviewable state. Current status: {{ ucwords(str_replace('_', ' ', $document->status)) }}.</p></div>
            @endcan
        </aside>
    </section>
</x-layout>
