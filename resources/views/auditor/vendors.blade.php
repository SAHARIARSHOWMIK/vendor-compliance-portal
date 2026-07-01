<x-layout :title="'Vendors (Read-only)'">
    <h1 class="text-2xl font-semibold mb-6">Vendors</h1>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Compliance</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Score</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($vendors as $vendor)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $vendor->name }}</td>
                        <td class="px-4 py-3"><x-vendor-status-badge :status="$vendor->status" /></td>
                        <td class="px-4 py-3">@if($vendor->compliance_status)<x-compliance-badge :status="$vendor->compliance_status" />@else —@endif</td>
                        <td class="px-4 py-3">{{ $vendor->compliance_score }}%</td>
                        <td class="px-4 py-3"><a href="{{ route('auditor.vendors.show', $vendor) }}" class="text-xs text-slate-600 hover:underline">View →</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $vendors->links() }}</div>
</x-layout>
