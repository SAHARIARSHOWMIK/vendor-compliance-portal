<x-layout :title="'Reports'">
    <h1 class="text-2xl font-semibold mb-6">Reports</h1>

    <div class="grid grid-cols-2 gap-4">
        @foreach ([
            ['compliance-summary', 'Compliance Summary', 'See which vendors are compliant, partially compliant, or non-compliant.'],
            ['missing-documents', 'Missing Documents', 'Find vendors with incomplete document submissions.'],
            ['expiring-documents', 'Expiring Documents', 'Track upcoming document expiries within 60 days.'],
            ['rejected-documents', 'Rejected Documents', 'Review documents that were rejected or need correction.'],
            ['vendor-onboarding', 'Vendor Onboarding Status', 'See where each vendor is in the onboarding pipeline.'],
            ['reviewer-workload', 'Reviewer Workload', 'See how many reviews each reviewer has completed.'],
            ['audit-log', 'Audit Trail Export', 'Full compliance evidence log of every system action.'],
        ] as [$slug, $title, $desc])
            <a href="{{ route('admin.reports.' . $slug) }}"
               class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
                <h2 class="font-semibold mb-1">{{ $title }}</h2>
                <p class="text-sm text-slate-500">{{ $desc }}</p>
            </a>
        @endforeach
    </div>
</x-layout>
