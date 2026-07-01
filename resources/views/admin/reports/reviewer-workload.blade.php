<x-layout :title="'Reviewer Workload Report'">
    <div class="text-sm text-slate-500 mb-2"><a href="{{ route('admin.reports.index') }}" class="hover:underline">Reports</a> /</div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Reviewer Workload</h1>
        <a href="{{ route('admin.reports.reviewer-workload.export') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-700">↓ Export CSV</a>
    </div>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Reviewer</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Role</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Total Reviews</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">This Month</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendors Assigned</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->role->label() }}</td>
                        <td class="px-4 py-3">{{ $user->total_reviews ?? 0 }}</td>
                        <td class="px-4 py-3">{{ $user->reviews_this_month ?? 0 }}</td>
                        <td class="px-4 py-3">{{ $user->vendors_assigned ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
