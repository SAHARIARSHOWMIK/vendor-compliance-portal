<x-layout :title="'Reports & Evidence'">
    <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div><div class="page-kicker">Assurance intelligence</div><h1 class="page-title">Reports & evidence</h1><p class="page-subtitle">Operational reports for compliance health, remediation, expiry risk, onboarding progress, reviewer workload, and audit evidence.</p></div>
        <a href="{{ route('admin.reports.audit-log') }}" class="btn-primary"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z"/></svg>Open audit trail</a>
    </div>

    @php
        $reports = [
            ['compliance-summary', 'Portfolio compliance', 'Compare vendor compliance status, risk level, ownership, and scores.', 'emerald', 'M4 19V9m5 10V5m5 14v-7m5 7V3'],
            ['missing-documents', 'Missing evidence', 'Identify vendors that cannot complete onboarding because required files are absent.', 'red', 'M12 9v3m0 4h.01M5 19h14L12 4 5 19z'],
            ['expiring-documents', 'Expiry exposure', 'Prioritize document renewals within configurable assurance windows.', 'amber', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['rejected-documents', 'Corrections & rejections', 'Track rejected evidence, reviewer comments, and required remediation.', 'orange', 'M6 18L18 6M6 6l12 12'],
            ['vendor-onboarding', 'Onboarding pipeline', 'Monitor vendors through invitation, submission, review, and approval stages.', 'indigo', 'M17 20h5v-2a4 4 0 00-4-4h-1m-5 6H2v-2a4 4 0 014-4h6a4 4 0 014 4v2zm0-10a4 4 0 100-8 4 4 0 000 8z'],
            ['reviewer-workload', 'Reviewer operations', 'Balance evidence workload and monitor human-review throughput.', 'sky', 'M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z'],
            ['audit-log', 'Audit evidence', 'Search and export every material workflow event with actor and vendor snapshots.', 'slate', 'M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z'],
        ];
    @endphp

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($reports as [$slug, $title, $description, $tone, $path])
            <a href="{{ route('admin.reports.' . $slug) }}" class="panel panel-hover group p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-{{ $tone }}-50 text-{{ $tone }}-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $path }}"/></svg></div>
                    <svg class="h-5 w-5 text-slate-300 transition group-hover:translate-x-1 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5l7 7-7 7"/></svg>
                </div>
                <h2 class="mt-5 text-base font-bold text-slate-900">{{ $title }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
                <div class="mt-5 text-xs font-semibold text-indigo-600">Open report →</div>
            </a>
        @endforeach
    </div>

    <div class="mt-6 panel border-indigo-200 bg-gradient-to-r from-slate-950 via-indigo-950 to-slate-950 p-6 text-white">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"><div><div class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-300">Evidence integrity</div><h2 class="mt-2 text-xl font-bold">Every export is recorded automatically</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-400">CSV exports create immutable audit events containing the actor, report type, request context, and timestamp. This supports evidence traceability without relying on manual record keeping.</p></div><a href="{{ route('admin.reports.audit-log') }}" class="btn bg-white text-slate-950 hover:bg-indigo-50">Inspect audit history</a></div>
    </div>
</x-layout>
