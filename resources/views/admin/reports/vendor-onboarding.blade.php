<x-layout :title="'Vendor Onboarding Status Report'">
    <div class="text-sm text-slate-500 mb-2"><a href="{{ route('admin.reports.index') }}" class="hover:underline">Reports</a> /</div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Vendor Onboarding Status</h1>
        <a href="{{ route('admin.reports.vendor-onboarding.export') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-700">↓ Export CSV</a>
    </div>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Compliance</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Invited</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Registered</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($vendors as $vendor)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $vendor->name }}</td>
                        <td class="px-4 py-3"><x-vendor-status-badge :status="$vendor->status" /></td>
                        <td class="px-4 py-3">{{ $vendor->compliance_score }}%</td>
                        <td class="px-4 py-3 text-slate-600">{{ $vendor->invited_at?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $vendor->registered_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
