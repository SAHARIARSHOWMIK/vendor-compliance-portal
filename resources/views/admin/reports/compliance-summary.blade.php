<x-layout :title="'Compliance Summary Report'">
    <div class="text-sm text-slate-500 mb-2"><a href="{{ route('admin.reports.index') }}" class="hover:underline">Reports</a> /</div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Compliance Summary Report</h1>
        <a href="{{ route('admin.reports.compliance-summary.export', request()->query()) }}"
           class="rounded bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-700">↓ Export CSV</a>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="risk_level" class="rounded border-slate-300 text-sm">
            <option value="">All risk levels</option>
            <option value="high" @selected(request('risk_level')==='high')>High</option>
            <option value="medium" @selected(request('risk_level')==='medium')>Medium</option>
            <option value="low" @selected(request('risk_level')==='low')>Low</option>
        </select>
        <button type="submit" class="rounded bg-slate-100 px-4 py-2 text-sm hover:bg-slate-200">Filter</button>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Category</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Risk</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Compliance</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Score</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Reviewer</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($vendors as $vendor)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $vendor->name }}</td>
                        <td class="px-4 py-3">{{ ucwords(str_replace('_',' ',$vendor->category)) }}</td>
                        <td class="px-4 py-3">{{ ucfirst($vendor->risk_level) }}</td>
                        <td class="px-4 py-3">
                            @if ($vendor->compliance_status)
                                <x-compliance-badge :status="$vendor->compliance_status" />
                            @else — @endif
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ $vendor->compliance_score }}%</td>
                        <td class="px-4 py-3 text-slate-600">{{ $vendor->assignedReviewer?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
