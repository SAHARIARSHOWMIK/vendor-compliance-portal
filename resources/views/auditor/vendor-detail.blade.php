<x-layout :title="$vendor->name . ' (Read-only)'">
    <div class="text-sm text-slate-500 mb-2"><a href="{{ route('auditor.vendors') }}" class="hover:underline">Vendors</a> /</div>
    <h1 class="text-2xl font-semibold mb-2">{{ $vendor->name }}</h1>
    <div class="flex gap-3 mb-6">
        <x-vendor-status-badge :status="$vendor->status" />
        @if ($vendor->compliance_status)<x-compliance-badge :status="$vendor->compliance_status" />@endif
    </div>

    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <h2 class="font-semibold mb-4">Document History &amp; Approvals</h2>
        @foreach ($vendor->documents as $doc)
            <div class="py-3 border-b border-slate-100 last:border-0">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-sm">{{ $doc->documentType->name }}</span>
                    <x-document-status-badge :status="$doc->status" />
                </div>
                @foreach ($doc->reviews as $review)
                    <div class="text-xs text-slate-500 mt-1 pl-3 border-l-2 border-slate-200">
                        {{ ucwords(str_replace('_',' ',$review->decision)) }} by {{ $review->reviewer?->name }} on {{ $review->reviewed_at?->format('d M Y') }}
                        @if ($review->comment) — "{{ $review->comment }}" @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold mb-4">Full Audit History</h2>
        <ul class="divide-y divide-slate-100">
            @foreach ($vendor->auditLogs as $log)
                <li class="py-2 text-sm flex justify-between">
                    <span>{{ ucwords(str_replace('_',' ',$log->event_type)) }} — {{ $log->description }}</span>
                    <span class="text-xs text-slate-400">{{ $log->occurred_at->format('d M Y H:i') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</x-layout>
