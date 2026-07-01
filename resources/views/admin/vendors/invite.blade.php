<x-layout :title="'Invite User — ' . $vendor->name">
    <div class="max-w-lg">
        <div class="text-sm text-slate-500 mb-2">
            <a href="{{ route('admin.vendors.show', $vendor) }}" class="hover:underline">{{ $vendor->name }}</a> /
        </div>
        <h1 class="text-2xl font-semibold mb-6">Invite Vendor User</h1>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-slate-600 mb-5">
                Send a portal invitation to the vendor contact. They will receive an email with a
                link to set their password and access the document upload portal.
            </p>

            @if ($errors->any())
                <div class="mb-4 rounded bg-red-50 text-red-700 text-sm px-4 py-3">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.vendors.invite', $vendor) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Contact Name *</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $vendor->contact_person) }}"
                        required class="w-full rounded border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $vendor->email) }}"
                        required class="w-full rounded border-slate-300 text-sm">
                    <p class="text-xs text-slate-400 mt-1">
                        An invitation will be sent to this address. If an account already exists for this
                        email it will be linked to this vendor.
                    </p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="rounded bg-slate-900 text-white px-5 py-2 text-sm font-medium hover:bg-slate-700">
                        Send Invitation
                    </button>
                    <a href="{{ route('admin.vendors.show', $vendor) }}"
                        class="rounded px-5 py-2 text-sm text-slate-600 hover:bg-slate-100">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layout>
