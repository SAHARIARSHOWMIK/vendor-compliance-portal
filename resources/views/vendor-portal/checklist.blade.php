<x-layout :title="'My Documents — ' . $vendor->name">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ $vendor->name }}</h1>
            <div class="flex items-center gap-3 mt-1">
                <x-compliance-badge :status="$vendor->compliance_status ?? 'documents_missing'" />
                <span class="text-sm text-slate-500">Compliance score: <strong>{{ $vendor->compliance_score }}%</strong></span>
            </div>
        </div>
        <a href="{{ route('vendor-portal.documents.upload-form', $vendor) }}"
           class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-700">
            Upload Document
        </a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded bg-green-50 text-green-800 text-sm px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    {{-- Required document checklist --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-5 py-4 border-b border-slate-100 font-semibold">Required Documents</div>
        <ul class="divide-y divide-slate-100">
            @foreach ($checklist as $item)
                <li class="flex items-center justify-between px-5 py-4">
                    <div class="flex-1">
                        <div class="font-medium text-sm">{{ $item['document_type']->name }}</div>
                        @if ($item['document_type']->requires_expiry_date)
                            <div class="text-xs text-slate-400 mt-0.5">Expiry date required</div>
                        @endif
                        @if ($item['document'] && $item['document']->review_comment && ! $item['is_approved'])
                            <div class="mt-1 text-xs text-orange-700 bg-orange-50 rounded px-2 py-1">
                                Reviewer note: {{ $item['document']->review_comment }}
                            </div>
                        @endif
                        @if ($item['document'])
                            <div class="text-xs text-slate-400 mt-0.5">
                                v{{ $item['document']->version_number }} •
                                {{ $item['document']->original_filename }}
                                @if ($item['document']->expiry_date)
                                    • Expires: {{ $item['document']->expiry_date->format('d M Y') }}
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 ml-4">
                        @if ($item['is_missing'])
                            <span class="text-xs text-red-600 font-medium">Required</span>
                            <a href="{{ route('vendor-portal.documents.upload-form', [$vendor, 'type_id' => $item['document_type']->id]) }}"
                               class="text-xs text-slate-700 border border-slate-300 rounded px-2 py-1 hover:bg-slate-50">
                                Upload
                            </a>
                        @else
                            <x-document-status-badge :status="$item['document']->status" />
                            @if ($item['is_rejected'])
                                <a href="{{ route('vendor-portal.documents.upload-form', [$vendor, 'type_id' => $item['document_type']->id]) }}"
                                   class="text-xs text-orange-700 border border-orange-300 rounded px-2 py-1 hover:bg-orange-50">
                                    Reupload
                                </a>
                            @endif
                            <a href="{{ route('vendor-portal.documents.download', $item['document']) }}"
                               class="text-xs text-slate-500 hover:text-slate-700">
                                ↓ Download
                            </a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Compliance progress bar --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-semibold text-sm mb-3">Overall Compliance</h2>
        <div class="w-full bg-slate-100 rounded-full h-3 mb-2">
            <div class="h-3 rounded-full {{ $vendor->compliance_score >= 100 ? 'bg-green-500' : ($vendor->compliance_score >= 60 ? 'bg-amber-400' : 'bg-red-400') }}"
                 style="width: {{ $vendor->compliance_score }}%"></div>
        </div>
        <div class="text-sm text-slate-600">
            {{ $vendor->compliance_score }}% complete —
            @php
                $missing  = collect($checklist)->filter(fn($i) => $i['is_missing'])->count();
                $approved = collect($checklist)->filter(fn($i) => $i['is_approved'])->count();
            @endphp
            {{ $approved }} approved, {{ $missing }} still required
        </div>
    </div>
</x-layout>
