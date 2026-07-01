<x-layout :title="'Auditor View'">
    <h1 class="text-2xl font-semibold mb-2">Auditor View</h1>
    <p class="text-slate-500 mb-6">Read-only access — vendor records, approval history, and audit trail.</p>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <a href="{{ route('auditor.vendors') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Total Vendors</div>
            <div class="text-3xl font-semibold mt-1">{{ $totalVendors }}</div>
        </a>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Fully Compliant</div>
            <div class="text-3xl font-semibold mt-1 text-green-600">{{ $fullyCompliant }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Non-Compliant</div>
            <div class="text-3xl font-semibold mt-1 text-red-600">{{ $nonCompliant }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <h2 class="font-semibold mb-4">Recent Activity</h2>
        <ul class="divide-y divide-slate-100">
            @foreach ($recentLogs as $log)
                <li class="py-2 text-sm flex justify-between">
                    <span>{{ ucwords(str_replace('_',' ',$log->event_type)) }} — {{ $log->vendor_name ?? 'System' }}</span>
                    <span class="text-xs text-slate-400">{{ $log->occurred_at->diffForHumans() }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <a href="{{ route('admin.reports.audit-log') }}" class="text-sm text-slate-700 hover:underline">Export full audit log →</a>
</x-layout>
