<x-layout :title="'Review: ' . $document->documentType->name">
    <div class="text-sm text-slate-500 mb-2">
        <a href="{{ route('reviewer.queue') }}" class="hover:underline">Review Queue</a> /
    </div>

    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ $document->documentType->name }}</h1>
            <div class="text-slate-500 text-sm mt-1">
                {{ $document->vendor->name }} •
                v{{ $document->version_number }} •
                Uploaded {{ $document->uploaded_at?->format('d M Y H:i') }}
                by {{ $document->uploader?->name }}
            </div>
        </div>
        <x-document-status-badge :status="$document->status" />
    </div>

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-5">

            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="font-semibold mb-4">Document Details</h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <dt class="text-slate-500">File name</dt>
                    <dd class="font-mono text-xs">{{ $document->original_filename }}</dd>
                    <dt class="text-slate-500">File size</dt>
                    <dd>{{ number_format($document->file_size_kb) }} KB</dd>
                    <dt class="text-slate-500">MIME type</dt>
                    <dd>{{ $document->mime_type }}</dd>
                    <dt class="text-slate-500">Version</dt>
                    <dd>v{{ $document->version_number }}</dd>
                    <dt class="text-slate-500">Expiry date</dt>
                    <dd>
                        @if ($document->expiry_date)
                            <span @class(['text-red-600 font-medium' => $document->expiry_date->isPast()])>
                                {{ $document->expiry_date->format('d M Y') }}
                                ({{ $document->expiry_date->isPast() ? 'expired' : $document->expiry_date->diffForHumans() }})
                            </span>
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="text-slate-500">Vendor risk level</dt>
                    <dd>{{ ucfirst($document->vendor->risk_level) }}</dd>
                    <dt class="text-slate-500">Vendor category</dt>
                    <dd>{{ ucwords(str_replace('_', ' ', $document->vendor->category)) }}</dd>
                </dl>
                <div class="mt-4">
                    <a href="{{ route('vendor-portal.documents.download', $document) }}"
                       class="inline-flex items-center gap-1 text-sm text-slate-700 border border-slate-300 rounded px-3 py-1.5 hover:bg-slate-50">
                        ↓ Download document
                    </a>
                </div>
            </div>

            @if ($document->notes)
                <div class="bg-white rounded-lg shadow p-5">
                    <h2 class="font-semibold mb-2">Vendor Notes</h2>
                    <p class="text-sm text-slate-600">{{ $document->notes }}</p>
                </div>
            @endif

            @if ($document->versions->isNotEmpty())
                <div class="bg-white rounded-lg shadow p-5">
                    <h2 class="font-semibold mb-4">Version History</h2>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($document->versions as $version)
                            <li class="py-3 flex items-center justify-between text-sm">
                                <div>
                                    <span class="font-medium">v{{ $version->version_number }}</span>
                                    <span class="text-slate-400 ml-2">{{ $version->original_filename }}</span>
                                    <span class="text-xs text-slate-400 ml-2">
                                        {{ $version->uploaded_at?->format('d M Y') }} by {{ $version->uploader?->name }}
                                    </span>
                                    @if ($version->status_at_snapshot)
                                        <x-document-status-badge :status="$version->status_at_snapshot" />
                                    @endif
                                </div>
                                <a href="{{ route('vendor-portal.documents.version-download', $version) }}"
                                   class="text-xs text-slate-500 hover:text-slate-700">↓ Download</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($document->reviews->isNotEmpty())
                <div class="bg-white rounded-lg shadow p-5">
                    <h2 class="font-semibold mb-4">Review History</h2>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($document->reviews as $review)
                            <li class="py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <x-document-status-badge :status="$review->decision" />
                                    <span class="text-slate-500">by {{ $review->reviewer?->name }}</span>
                                    <span class="text-xs text-slate-400">{{ $review->reviewed_at?->format('d M Y H:i') }}</span>
                                    @if ($review->document_version)
                                        <span class="text-xs text-slate-400">on v{{ $review->document_version }}</span>
                                    @endif
                                </div>
                                @if ($review->comment)
                                    <p class="mt-1 text-slate-600 pl-2 border-l-2 border-slate-200">{{ $review->comment }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @can('decide', $document)
            <div>
                <div class="bg-white rounded-lg shadow p-5 sticky top-6">
                    <h2 class="font-semibold mb-4">Make Decision</h2>

                    @if ($errors->any())
                        <div class="mb-3 rounded bg-red-50 text-red-700 text-xs px-3 py-2">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('reviewer.documents.decide', $document) }}">
                        @csrf

                        <div class="space-y-2 mb-4">
                            @foreach ([
                                'approved'             => ['✓ Approve', 'bg-green-600 hover:bg-green-700'],
                                'correction_requested' => ['↺ Request Correction', 'bg-orange-500 hover:bg-orange-600'],
                                'rejected'             => ['✕ Reject', 'bg-red-600 hover:bg-red-700'],
                                'need_more_info'       => ['? Need More Info', 'bg-blue-600 hover:bg-blue-700'],
                                'escalated'            => ['↑ Escalate', 'bg-purple-600 hover:bg-purple-700'],
                            ] as $value => [$label, $classes])
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <input type="radio" name="decision" value="{{ $value }}"
                                        @checked(old('decision') === $value)
                                        class="text-slate-700">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">
                                Comment
                                <span class="text-slate-400 font-normal text-xs">(required for rejection / correction)</span>
                            </label>
                            <textarea name="comment" rows="4"
                                class="w-full rounded border-slate-300 text-sm"
                                placeholder="Explain the decision to the vendor...">{{ old('comment') }}</textarea>
                        </div>

                        <button type="submit"
                            class="w-full rounded bg-slate-900 text-white py-2 text-sm font-medium hover:bg-slate-700">
                            Submit Decision
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="bg-amber-50 rounded-lg p-4 text-sm text-amber-800">
                This document is not in a reviewable state (status: {{ $document->status }}).
            </div>
        @endcan
    </div>
</x-layout>
