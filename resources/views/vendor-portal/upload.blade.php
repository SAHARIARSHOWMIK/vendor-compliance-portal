<x-layout :title="'Upload Document'">
    <div class="max-w-xl">
        <div class="text-sm text-slate-500 mb-2">
            <a href="{{ route('vendor-portal.checklist') }}" class="hover:underline">My Documents</a> /
        </div>

        <h1 class="text-2xl font-semibold mb-6">
            {{ $existingDocument ? 'Replace Document' : 'Upload Document' }}
        </h1>

        @if ($existingDocument && $existingDocument->review_comment)
            <div class="mb-4 rounded bg-orange-50 border border-orange-200 text-orange-800 text-sm px-4 py-3">
                <strong>Reviewer note:</strong> {{ $existingDocument->review_comment }}
            </div>
        @endif

        <form method="POST" action="{{ route('vendor-portal.documents.store', $vendor) }}"
              enctype="multipart/form-data"
              class="bg-white rounded-lg shadow p-6 space-y-5">
            @csrf

            @if ($errors->any())
                <div class="rounded bg-red-50 text-red-700 text-sm px-4 py-3">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Document type selector --}}
            <div>
                <label class="block text-sm font-medium mb-1">Document Type *</label>
                <select name="document_type_id" id="document_type_id" required
                    class="w-full rounded border-slate-300 text-sm">
                    <option value="">Select document type</option>
                    @foreach ($documentTypes as $dt)
                        <option value="{{ $dt->id }}"
                            data-requires-expiry="{{ $dt->requires_expiry_date ? 'true' : 'false' }}"
                            data-allowed-types="{{ $dt->allowed_file_types }}"
                            data-max-size="{{ $dt->max_file_size_kb }}"
                            @selected(old('document_type_id', $preselectedType?->id) == $dt->id)>
                            {{ $dt->name }}
                            @if ($dt->requires_expiry_date) (expiry required) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- File input --}}
            <div>
                <label class="block text-sm font-medium mb-1">File *</label>
                <input type="file" name="file" required
                    class="w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                <p id="file-hint" class="text-xs text-slate-400 mt-1">
                    Allowed file types and size limit depend on the selected document type.
                </p>
            </div>

            {{-- Expiry date --}}
            <div id="expiry-wrapper">
                <label class="block text-sm font-medium mb-1">
                    Expiry Date
                    <span id="expiry-required-badge" class="hidden text-red-500">*</span>
                </label>
                <input type="date" name="expiry_date"
                    value="{{ old('expiry_date', $existingDocument?->expiry_date?->format('Y-m-d')) }}"
                    class="w-full rounded border-slate-300 text-sm"
                    min="{{ now()->addDay()->format('Y-m-d') }}">
                <p class="text-xs text-slate-400 mt-1">
                    Required for business licenses, insurance certificates, contracts, and safety certificates.
                </p>
            </div>

            {{-- Notes / version description --}}
            <div>
                <label class="block text-sm font-medium mb-1">Notes / Version Description</label>
                <textarea name="notes" rows="2"
                    class="w-full rounded border-slate-300 text-sm"
                    placeholder="{{ $existingDocument ? 'Describe what changed in this version...' : 'Optional notes about this document...' }}">{{ old('notes') }}</textarea>
            </div>

            @if ($existingDocument)
                <div class="rounded bg-blue-50 text-blue-700 text-sm px-4 py-3">
                    You are replacing <strong>{{ $existingDocument->original_filename }}</strong>
                    (v{{ $existingDocument->version_number }}).
                    The previous version will be preserved in version history.
                </div>
            @endif

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="rounded bg-slate-900 text-white px-5 py-2 text-sm font-medium hover:bg-slate-700">
                    {{ $existingDocument ? 'Upload Replacement' : 'Upload Document' }}
                </button>
                <a href="{{ route('vendor-portal.checklist') }}"
                    class="rounded px-5 py-2 text-sm text-slate-600 hover:bg-slate-100">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Dynamically show/hide the expiry-required marker and update
        // the file hint when the document type changes.
        const typeSelect  = document.getElementById('document_type_id');
        const expiryBadge = document.getElementById('expiry-required-badge');
        const fileHint    = document.getElementById('file-hint');

        function updateForSelectedType() {
            const opt     = typeSelect.options[typeSelect.selectedIndex];
            const expiry  = opt.dataset.requiresExpiry === 'true';
            const allowed = opt.dataset.allowedTypes  || '';
            const maxKb   = opt.dataset.maxSize        || '';

            expiryBadge.classList.toggle('hidden', !expiry);

            if (allowed) {
                const maxMb = maxKb ? (maxKb / 1024).toFixed(0) + ' MB' : '';
                fileHint.textContent = `Accepted: ${allowed.toUpperCase()}${maxMb ? ' • Max size: ' + maxMb : ''}`;
            } else {
                fileHint.textContent = 'Allowed file types and size limit depend on the selected document type.';
            }
        }

        typeSelect.addEventListener('change', updateForSelectedType);
        updateForSelectedType();
    </script>
</x-layout>
