<x-layout :title="'Edit — ' . $vendor->name">
    <div class="max-w-2xl">
        <h1 class="text-2xl font-semibold mb-6">Edit Vendor — {{ $vendor->name }}</h1>

        <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}" class="space-y-5 bg-white rounded-lg shadow p-6">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="rounded bg-red-50 text-red-700 text-sm px-4 py-3">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Vendor Name *</label>
                    <input type="text" name="name" value="{{ old('name', $vendor->name) }}" required
                        class="w-full rounded border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Registration Number</label>
                    <input type="text" name="registration_number" value="{{ old('registration_number', $vendor->registration_number) }}"
                        class="w-full rounded border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Category *</label>
                    <select name="category" required class="w-full rounded border-slate-300 text-sm">
                        @foreach (['general_supplier' => 'General Supplier', 'it_vendor' => 'IT Vendor', 'contractor' => 'Contractor', 'consultant' => 'Consultant / Freelancer', 'high_risk' => 'High-Risk Vendor'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $vendor->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Risk Level *</label>
                    <select name="risk_level" required class="w-full rounded border-slate-300 text-sm">
                        @foreach (['low', 'medium', 'high'] as $r)
                            <option value="{{ $r }}" @selected(old('risk_level', $vendor->risk_level) === $r)>{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Assign Reviewer</label>
                    <select name="assigned_reviewer_id" class="w-full rounded border-slate-300 text-sm">
                        <option value="">Not assigned</option>
                        @foreach ($reviewers as $reviewer)
                            <option value="{{ $reviewer->id }}" @selected(old('assigned_reviewer_id', $vendor->assigned_reviewer_id) == $reviewer->id)>{{ $reviewer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $vendor->contact_person) }}"
                        class="w-full rounded border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $vendor->email) }}"
                        class="w-full rounded border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}"
                        class="w-full rounded border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Country</label>
                    <input type="text" name="country" value="{{ old('country', $vendor->country) }}" maxlength="2"
                        class="w-full rounded border-slate-300 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Address</label>
                    <textarea name="address" rows="2" class="w-full rounded border-slate-300 text-sm">{{ old('address', $vendor->address) }}</textarea>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Internal Notes</label>
                    <textarea name="internal_notes" rows="3" class="w-full rounded border-slate-300 text-sm">{{ old('internal_notes', $vendor->internal_notes) }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="rounded bg-slate-900 text-white px-5 py-2 text-sm font-medium hover:bg-slate-700">Save Changes</button>
                <a href="{{ route('admin.vendors.show', $vendor) }}" class="rounded px-5 py-2 text-sm text-slate-600 hover:bg-slate-100">Cancel</a>
            </div>
        </form>
    </div>
</x-layout>
