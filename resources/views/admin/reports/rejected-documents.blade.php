<x-layout :title="'Rejected Documents Report'">
    <div class="text-sm text-slate-500 mb-2"><a href="{{ route('admin.reports.index') }}" class="hover:underline">Reports</a> /</div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Rejected Documents Report</h1>
        <a href="{{ route('admin.reports.rejected-documents.export') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-700">↓ Export CSV</a>
    </div>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Document</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Reviewer</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Comment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($documents as $doc)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $doc->vendor->name }}</td>
                        <td class="px-4 py-3">{{ $doc->documentType->name }}</td>
                        <td class="px-4 py-3"><x-document-status-badge :status="$doc->status" /></td>
                        <td class="px-4 py-3 text-slate-600">{{ $doc->reviewer?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600 max-w-xs truncate">{{ $doc->review_comment ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No rejected documents.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layout>
