<x-layout :title="'Expiring Documents Report'">
    <div class="text-sm text-slate-500 mb-2"><a href="{{ route('admin.reports.index') }}" class="hover:underline">Reports</a> /</div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Expiring Documents Report</h1>
        <a href="{{ route('admin.reports.expiring-documents.export', ['within_days' => $withinDays]) }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-700">↓ Export CSV</a>
    </div>

    <form method="GET" class="flex gap-3 mb-5">
        <select name="within_days" class="rounded border-slate-300 text-sm">
            <option value="7" @selected($withinDays==7)>Next 7 days</option>
            <option value="30" @selected($withinDays==30)>Next 30 days</option>
            <option value="60" @selected($withinDays==60)>Next 60 days</option>
        </select>
        <button type="submit" class="rounded bg-slate-100 px-4 py-2 text-sm hover:bg-slate-200">Apply</button>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Document</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Expiry Date</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Days Remaining</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Risk</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($documents as $doc)
                    @php $days = $doc->daysUntilExpiry(); @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $doc->vendor->name }}</td>
                        <td class="px-4 py-3">{{ $doc->documentType->name }}</td>
                        <td class="px-4 py-3">{{ $doc->expiry_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span @class(['font-medium', 'text-red-600' => $days <= 7, 'text-amber-600' => $days > 7 && $days <= 30])>
                                {{ $days }} day{{ $days === 1 ? '' : 's' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ ucfirst($doc->vendor->risk_level) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No documents expiring in this window.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout>
