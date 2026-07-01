<x-layout :title="$vendor->name">
    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.vendors.index') }}" class="hover:underline">Vendors</a> /
            </div>
            <h1 class="text-2xl font-semibold">{{ $vendor->name }}</h1>
            <div class="flex items-center gap-3 mt-1">
                <x-vendor-status-badge :status="$vendor->status" />
                @if ($vendor->compliance_status)
                    <x-compliance-badge :status="$vendor->compliance_status" />
                @endif
                <span class="text-sm text-slate-500">Score: <strong>{{ $vendor->compliance_score }}%</strong></span>
            </div>
        </div>
        <div class="flex gap-2">
            @can('update', $vendor)
                <a href="{{ route('admin.vendors.edit', $vendor) }}"
                   class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Edit</a>
            @endcan
            @can('invite', $vendor)
                <a href="{{ route('admin.vendors.invite-form', $vendor) }}"
                   class="rounded bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-700">Invite User</a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        {{-- Left: profile + compliance --}}
        <div class="col-span-2 space-y-6">

            {{-- Profile --}}
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="font-semibold mb-4">Vendor Profile</h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <dt class="text-slate-500">Registration No.</dt>
                    <dd>{{ $vendor->registration_number ?? '—' }}</dd>
                    <dt class="text-slate-500">Category</dt>
                    <dd>{{ ucwords(str_replace('_', ' ', $vendor->category)) }}</dd>
                    <dt class="text-slate-500">Risk Level</dt>
                    <dd>{{ ucfirst($vendor->risk_level) }}</dd>
                    <dt class="text-slate-500">Contact Person</dt>
                    <dd>{{ $vendor->contact_person ?? '—' }}</dd>
                    <dt class="text-slate-500">Email</dt>
                    <dd>{{ $vendor->email ?? '—' }}</dd>
                    <dt class="text-slate-500">Phone</dt>
                    <dd>{{ $vendor->phone ?? '—' }}</dd>
                    <dt class="text-slate-500">Country</dt>
                    <dd>{{ $vendor->country ?? '—' }}</dd>
                    <dt class="text-slate-500">Reviewer</dt>
                    <dd>{{ $vendor->assignedReviewer?->name ?? '—' }}</dd>
                </dl>
            </div>

            {{-- Document checklist --}}
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="font-semibold mb-4">Required Document Checklist</h2>
                @if ($requiredDocTypes->isEmpty())
                    <p class="text-sm text-slate-400">No required documents defined for this vendor category.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($requiredDocTypes as $docType)
                            @php
                                $uploaded = $vendor->documents->firstWhere('document_type_id', $docType->id);
                            @endphp
                            <li class="flex items-center justify-between text-sm py-2 border-b border-slate-100 last:border-0">
                                <span>{{ $docType->name }}</span>
                                @if ($uploaded)
                                    <x-document-status-badge :status="$uploaded->status" />
                                @else
                                    <span class="inline-block rounded-full bg-slate-100 text-slate-500 text-xs px-2 py-0.5">Missing</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Audit log --}}
            <div class="bg-white rounded-lg shadow p-5">
                <h2 class="font-semibold mb-4">Recent Activity</h2>
                <ol class="relative border-l border-slate-200 space-y-4 ml-2">
                    @forelse ($vendor->auditLogs as $log)
                        <li class="ml-4 text-sm">
                            <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full bg-slate-300"></div>
                            <time class="text-xs text-slate-400">{{ $log->occurred_at->diffForHumans() }}</time>
                            <p class="font-medium">{{ $log->event_type }}</p>
                            <p class="text-slate-600">{{ $log->description }}</p>
                        </li>
                    @empty
                        <li class="ml-4 text-sm text-slate-400">No activity yet.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        {{-- Right: sidebar actions --}}
        <div class="space-y-4">
            @can('suspend', $vendor)
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-medium text-sm mb-3">Suspend Vendor</h3>
                    <form method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">
                        @csrf
                        <textarea name="reason" required placeholder="Reason for suspension..."
                            class="w-full text-sm rounded border-slate-300 mb-2" rows="2"></textarea>
                        <button type="submit"
                            class="w-full rounded bg-red-600 text-white text-sm py-2 hover:bg-red-700">
                            Suspend
                        </button>
                    </form>
                </div>
            @endcan

            @can('reinstate', $vendor)
                <div class="bg-white rounded-lg shadow p-4">
                    <form method="POST" action="{{ route('admin.vendors.reinstate', $vendor) }}">
                        @csrf
                        <button type="submit"
                            class="w-full rounded bg-green-600 text-white text-sm py-2 hover:bg-green-700">
                            Reinstate Vendor
                        </button>
                    </form>
                </div>
            @endcan

            @can('assignReviewer', $vendor)
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-medium text-sm mb-3">Assign Reviewer</h3>
                    <form method="POST" action="{{ route('admin.vendors.assign-reviewer', $vendor) }}">
                        @csrf
                        <select name="reviewer_id" class="w-full rounded border-slate-300 text-sm mb-2">
                            <option value="">— Not assigned —</option>
                            @foreach ($reviewers as $r)
                                <option value="{{ $r->id }}" @selected($vendor->assigned_reviewer_id == $r->id)>{{ $r->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full rounded bg-slate-900 text-white text-sm py-2 hover:bg-slate-700">
                            Update
                        </button>
                    </form>
                </div>
            @endcan

            {{-- Vendor users --}}
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-medium text-sm mb-3">Vendor Users</h3>
                @forelse ($vendor->vendorUsers as $vu)
                    <div class="text-sm py-2 border-b border-slate-100 last:border-0">
                        <div class="font-medium">{{ $vu->user->name }}</div>
                        <div class="text-slate-500 text-xs">{{ $vu->user->email }}</div>
                        <div class="text-xs mt-0.5">
                            <span @class([
                                'inline-block rounded-full px-1.5 py-0.5',
                                'bg-green-100 text-green-700' => $vu->isAccepted(),
                                'bg-amber-100 text-amber-700' => $vu->isPending(),
                            ])>{{ ucfirst($vu->invitation_status) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">No users invited yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layout>
