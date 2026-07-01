<x-layout :title="'Audit Log'">
    <div class="text-sm text-slate-500 mb-2"><a href="{{ route('admin.reports.index') }}" class="hover:underline">Reports</a> /</div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Audit Trail</h1>
        <a href="{{ route('admin.reports.audit-log.export', request()->query()) }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-700">↓ Export CSV</a>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="vendor_id" class="rounded border-slate-300 text-sm">
            <option value="">All vendors</option>
            @foreach ($vendors as $v)
                <option value="{{ $v->id }}" @selected(request('vendor_id')==$v->id)>{{ $v->name }}</option>
            @endforeach
        </select>
        <select name="event_type" class="rounded border-slate-300 text-sm">
            <option value="">All event types</option>
            @foreach ($eventTypes as $type)
                <option value="{{ $type }}" @selected(request('event_type')===$type)>{{ ucwords(str_replace('_',' ',$type)) }}</option>
            @endforeach
        </select>
        <input type="date" name="from_date" value="{{ request('from_date') }}" class="rounded border-slate-300 text-sm">
        <input type="date" name="to_date" value="{{ request('to_date') }}" class="rounded border-slate-300 text-sm">
        <button type="submit" class="rounded bg-slate-100 px-4 py-2 text-sm hover:bg-slate-200">Filter</button>
        <a href="{{ route('admin.reports.audit-log') }}" class="rounded px-4 py-2 text-sm text-slate-500 hover:bg-slate-100">Clear</a>
    </form>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Timestamp</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Actor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Event</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $log->occurred_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->actor_name ?? 'System' }}</td>
                        <td class="px-4 py-3">{{ $log->vendor_name ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="text-xs bg-slate-100 rounded px-2 py-0.5">{{ $log->event_type }}</span></td>
                        <td class="px-4 py-3 text-slate-600">{{ $log->description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layout>
