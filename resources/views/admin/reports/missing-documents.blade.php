<x-layout :title="'Missing Documents Report'">
    <div class="text-sm text-slate-500 mb-2"><a href="{{ route('admin.reports.index') }}" class="hover:underline">Reports</a> /</div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Missing Documents Report</h1>
        <a href="{{ route('admin.reports.missing-documents.export') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-700">↓ Export CSV</a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Category</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Risk</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Missing Document</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $row['vendor']->name }}</td>
                        <td class="px-4 py-3">{{ ucwords(str_replace('_',' ',$row['vendor']->category)) }}</td>
                        <td class="px-4 py-3">{{ ucfirst($row['vendor']->risk_level) }}</td>
                        <td class="px-4 py-3 text-red-600 font-medium">{{ $row['document_type']->name }}</td>
                        <td class="px-4 py-3"><x-vendor-status-badge :status="$row['vendor']->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No missing documents — all vendors are up to date. 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout>
