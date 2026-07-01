<x-layout :title="'Review Queue'">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Review Queue</h1>
        <span class="text-sm text-slate-500">{{ $documents->total() }} document(s) awaiting review</span>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded bg-green-50 text-green-800 text-sm px-4 py-3">{{ session('status') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="vendor_id" class="rounded border-slate-300 text-sm">
            <option value="">All vendors</option>
            @foreach ($vendors as $v)
                <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->name }}</option>
            @endforeach
        </select>
        <select name="risk_level" class="rounded border-slate-300 text-sm">
            <option value="">All risk levels</option>
            <option value="high"   @selected(request('risk_level') === 'high')>High risk</option>
            <option value="medium" @selected(request('risk_level') === 'medium')>Medium risk</option>
            <option value="low"    @selected(request('risk_level') === 'low')>Low risk</option>
        </select>
        @if (auth()->user()->isReviewer())
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="show_all" value="1" @checked(request('show_all'))>
                Show all vendors
            </label>
        @endif
        <button type="submit" class="rounded bg-slate-100 px-4 py-2 text-sm hover:bg-slate-200">Filter</button>
        <a href="{{ route('reviewer.queue') }}" class="rounded px-4 py-2 text-sm text-slate-500 hover:bg-slate-100">Clear</a>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Document</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Risk</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Uploaded</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Expiry</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Priority</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($documents as $doc)
                    @php
                        $isHighPriority = $doc->vendor->risk_level === 'high'
                            || ($doc->expiry_date && $doc->expiry_date->diffInDays(now()) <= 30);
                    @endphp
                    <tr class="hover:bg-slate-50 {{ $isHighPriority ? 'bg-red-50/30' : '' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $doc->vendor->name }}</div>
                            <div class="text-xs text-slate-400">{{ ucwords(str_replace('_', ' ', $doc->vendor->category)) }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $doc->documentType->name }}</div>
                            <div class="text-xs text-slate-400">v{{ $doc->version_number }}</div>
                        </td>
                        <td class="px-4 py-3"><x-document-status-badge :status="$doc->status" /></td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-block rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-red-100 text-red-700'   => $doc->vendor->risk_level === 'high',
                                'bg-amber-100 text-amber-700' => $doc->vendor->risk_level === 'medium',
                                'bg-green-100 text-green-700' => $doc->vendor->risk_level === 'low',
                            ])>{{ ucfirst($doc->vendor->risk_level) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $doc->uploaded_at?->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            @if ($doc->expiry_date)
                                <span @class([
                                    'text-red-600 font-medium' => $doc->expiry_date->isPast(),
                                    'text-amber-600'           => ! $doc->expiry_date->isPast() && $doc->expiry_date->diffInDays(now()) <= 30,
                                    'text-slate-600'           => $doc->expiry_date->diffInDays(now()) > 30,
                                ])>{{ $doc->expiry_date->format('d M Y') }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($isHighPriority)
                                <span class="inline-block rounded-full bg-red-100 text-red-700 text-xs px-2 py-0.5 font-medium">High</span>
                            @else
                                <span class="text-slate-400 text-xs">Normal</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('reviewer.documents.show', $doc) }}"
                               class="rounded bg-slate-900 text-white text-xs px-3 py-1.5 hover:bg-slate-700">
                                Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                            No documents pending review. 🎉
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $documents->links() }}</div>
</x-layout>
