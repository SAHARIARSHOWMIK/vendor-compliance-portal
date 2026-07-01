<x-layout :title="'Admin Dashboard'">
    <h1 class="text-2xl font-semibold mb-1">Admin Dashboard</h1>
    <p class="text-slate-500 mb-6">Welcome, {{ auth()->user()->name }}.</p>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <a href="{{ route('admin.vendors.index') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Total Vendors</div>
            <div class="text-3xl font-semibold mt-1">{{ $totalVendors }}</div>
        </a>
        <a href="{{ route('admin.vendors.index', ['compliance_status' => 'fully_compliant']) }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Fully Compliant</div>
            <div class="text-3xl font-semibold mt-1 text-green-600">{{ $fullyCompliant }}</div>
        </a>
        <a href="{{ route('admin.vendors.index', ['compliance_status' => 'non_compliant']) }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Non-Compliant</div>
            <div class="text-3xl font-semibold mt-1 text-red-600">{{ $nonCompliant }}</div>
        </a>
        <a href="{{ route('admin.vendors.index', ['compliance_status' => 'under_review']) }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Under Review</div>
            <div class="text-3xl font-semibold mt-1 text-purple-600">{{ $underReview }}</div>
        </a>
    </div>

    <div class="grid grid-cols-4 gap-4 mb-6">
        <a href="{{ route('reviewer.queue') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Pending Review</div>
            <div class="text-3xl font-semibold mt-1">{{ $pendingReviewDocs }}</div>
        </a>
        <a href="{{ route('admin.reports.expiring-documents') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Expiring Soon</div>
            <div class="text-3xl font-semibold mt-1 text-amber-600">{{ $expiringSoonDocs }}</div>
        </a>
        <a href="{{ route('admin.reports.rejected-documents') }}" class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Rejected Documents</div>
            <div class="text-3xl font-semibold mt-1 text-red-600">{{ $rejectedDocs }}</div>
        </a>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs text-slate-500 uppercase tracking-wide">High-Risk Vendors</div>
            <div class="text-3xl font-semibold mt-1 text-orange-600">{{ $highRiskVendors }}</div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        {{-- Score distribution --}}
        <div class="col-span-1 bg-white rounded-lg shadow p-5">
            <h2 class="font-semibold mb-4">Compliance Score Distribution</h2>
            <div class="space-y-2">
                @foreach ($scoreDistribution as $range => $count)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="w-12 text-slate-500">{{ $range }}%</span>
                        <div class="flex-1 bg-slate-100 rounded-full h-2">
                            <div class="bg-slate-700 h-2 rounded-full" style="width: {{ $totalVendors > 0 ? ($count / max($totalVendors,1)) * 100 : 0 }}%"></div>
                        </div>
                        <span class="w-6 text-right font-medium">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Recent activity --}}
        <div class="col-span-2 bg-white rounded-lg shadow p-5">
            <h2 class="font-semibold mb-4">Recent Activity</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentActivity as $log)
                    <li class="py-2 text-sm flex items-center justify-between">
                        <div>
                            <span class="font-medium">{{ ucwords(str_replace('_', ' ', $log->event_type)) }}</span>
                            <span class="text-slate-500"> — {{ $log->vendor_name ?? 'System' }}</span>
                        </div>
                        <span class="text-xs text-slate-400">{{ $log->occurred_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-400">No activity yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.reports.index') }}" class="text-sm text-slate-700 hover:underline">View all reports →</a>
    </div>
</x-layout>
