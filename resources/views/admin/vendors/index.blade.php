<x-layout :title="'Vendors'">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Vendors</h1>
        @can('create', App\Models\Vendor::class)
            <a href="{{ route('admin.vendors.create') }}"
               class="inline-flex items-center gap-2 rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-700">
                + New Vendor
            </a>
        @endcan
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        <select name="status" class="rounded border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (['draft','invited','registered','documents_pending','under_review','correction_required','partially_approved','fully_compliant','expiring_soon','non_compliant','suspended','archived'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
        <select name="category" class="rounded border-slate-300 text-sm">
            <option value="">All categories</option>
            @foreach (['general_supplier','it_vendor','contractor','consultant','high_risk'] as $c)
                <option value="{{ $c }}" @selected(request('category') === $c)>{{ ucwords(str_replace('_', ' ', $c)) }}</option>
            @endforeach
        </select>
        <select name="risk_level" class="rounded border-slate-300 text-sm">
            <option value="">All risk levels</option>
            <option value="low" @selected(request('risk_level') === 'low')>Low</option>
            <option value="medium" @selected(request('risk_level') === 'medium')>Medium</option>
            <option value="high" @selected(request('risk_level') === 'high')>High</option>
        </select>
        <button type="submit" class="rounded bg-slate-100 px-4 py-2 text-sm hover:bg-slate-200">Filter</button>
        <a href="{{ route('admin.vendors.index') }}" class="rounded px-4 py-2 text-sm text-slate-500 hover:bg-slate-100">Clear</a>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Category</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Risk</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Compliance</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Score</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Reviewer</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($vendors as $vendor)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ $vendor->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ ucwords(str_replace('_', ' ', $vendor->category)) }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-block rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-800' => $vendor->risk_level === 'low',
                                'bg-amber-100 text-amber-800' => $vendor->risk_level === 'medium',
                                'bg-red-100 text-red-800'    => $vendor->risk_level === 'high',
                            ])>{{ ucfirst($vendor->risk_level) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <x-vendor-status-badge :status="$vendor->status" />
                        </td>
                        <td class="px-4 py-3">
                            @if ($vendor->compliance_status)
                                <x-compliance-badge :status="$vendor->compliance_status" />
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold">{{ $vendor->compliance_score }}%</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $vendor->assignedReviewer?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.vendors.show', $vendor) }}"
                               class="text-slate-600 hover:text-slate-900 text-xs font-medium">View →</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">No vendors found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $vendors->links() }}</div>
</x-layout>
